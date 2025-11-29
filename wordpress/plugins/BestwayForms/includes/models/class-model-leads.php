<?php
if (!defined('ABSPATH')) exit;

class BestwayForms_Model_Leads extends BestwayForms_Model {
    protected $table_name = 'gdzl_leads';
    
    public function get_leads_count() {
        global $wpdb;
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->get_table_name()}");
        return $count ?: 0;
    }
    
    public function get_leads_count_by_source($source) {
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->get_table_name()} WHERE source = %s",
            $source
        ));
        return $count ?: 0;
    }
    
    public function get_leads_count_by_status($status) {
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->get_table_name()} WHERE status = %s",
            $status
        ));
        return $count ?: 0;
    }
    
    public function get_recent_leads($limit = 10) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->get_table_name()} ORDER BY created_at DESC LIMIT %d",
            $limit
        ));
        
        return $results ?: [];
    }
    
    public function get_all_leads() {
        global $wpdb;
        
        $leads = $wpdb->get_results("SELECT * FROM {$this->get_table_name()} ORDER BY created_at DESC");
        
        // Добавляем лиды из WooCommerce если интеграция включена
        if (class_exists('BestwayForms_Model_WooCommerce') && get_option('bestway_forms_wc_enabled')) {
            $wc_model = new BestwayForms_Model_WooCommerce();
            $wc_leads = $wc_model->get_all_wc_leads();
            
            foreach ($wc_leads as $wc_lead) {
                $lead = new stdClass();
                $lead->id = 'wc_' . $wc_lead->id;
                $lead->form_id = 0;
                $lead->form_data = $wc_lead->customer_data;
                $lead->source = 'woocommerce';
                $lead->n8n_response = $wc_lead->n8n_response;
                $lead->ai_processed_data = $wc_lead->ai_processed_data;
                $lead->status = 'new';
                $lead->created_at = $wc_lead->created_at;
                $lead->order_id = $wc_lead->order_id;
                $lead->order_data = $wc_lead->order_data;
                
                $leads[] = $lead;
            }
            
            // Сортируем по дате
            usort($leads, function($a, $b) {
                return strtotime($b->created_at) - strtotime($a->created_at);
            });
        }
        
        return $leads;
    }
    
    public function get_processing_history() {
        $leads = $this->get_all_leads();
        
        if (class_exists('BestwayForms_Model_WooCommerce')) {
            $wc_model = new BestwayForms_Model_WooCommerce();
            $wc_leads = $wc_model->get_all_wc_leads();
            
            foreach ($wc_leads as $wc_lead) {
                $wc_lead->source = 'woocommerce';
            }
            
            $all_items = array_merge($leads, $wc_leads);
        } else {
            $all_items = $leads;
        }
        
        usort($all_items, function($a, $b) {
            return strtotime($b->created_at) - strtotime($a->created_at);
        });
        
        return $all_items;
    }
    
    public function process_lead($form_data) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->get_table_name(),
            [
                'form_id' => $form_data['form_id'],
                'form_data' => json_encode($form_data),
                'source' => 'form',
                'status' => 'new'
            ],
            ['%d', '%s', '%s', '%s']
        );
        
        if ($result) {
            $lead_id = $wpdb->insert_id;
            
            $n8n_result = $this->send_to_n8n($lead_id, $form_data);
            $ai_result = $this->process_with_ai($lead_id, $form_data);
            
            $update_data = [];
            if ($n8n_result) {
                $update_data['n8n_response'] = json_encode($n8n_result);
            }
            if ($ai_result) {
                $update_data['ai_processed_data'] = json_encode($ai_result);
            }
            
            if (!empty($update_data)) {
                $wpdb->update(
                    $this->get_table_name(),
                    $update_data,
                    ['id' => $lead_id],
                    ['%s', '%s'],
                    ['%d']
                );
            }
            
            BestwayForms::log("Lid created: ID {$lead_id}, form: {$form_data['form_id']}");
            return $lead_id;
        }
        
        return false;
    }
    
    private function send_to_n8n($lead_id, $form_data) {
        if (!get_option('bestway_forms_n8n_enabled')) return null;
        
        $webhook_url = get_option('bestway_forms_n8n_webhook_url');
        if (!$webhook_url) return null;
        
        $api_key = get_option('bestway_forms_n8n_api_key');
        
        $headers = ['Content-Type' => 'application/json'];
        if ($api_key) {
            $headers['Authorization'] = 'Bearer ' . $api_key;
        }
        
        $payload = [
            'lead_id' => $lead_id,
            'form_data' => $form_data,
            'source' => 'bestway_forms',
            'timestamp' => current_time('mysql')
        ];
        
        $response = wp_remote_post($webhook_url, [
            'headers' => $headers,
            'body' => json_encode($payload),
            'timeout' => 15
        ]);
        
        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $json_response = json_decode($body, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                BestwayForms::log("n8n response received for lead {$lead_id}");
                return $json_response;
            }
            
            BestwayForms::log("n8n raw response for lead {$lead_id}: " . $body);
            return ['raw_response' => $body];
        }
        
        BestwayForms::log("n8n error for lead {$lead_id}: " . $response->get_error_message(), 'error');
        return null;
    }
    
    private function process_with_ai($lead_id, $form_data) {
        if (!get_option('bestway_forms_ai_enabled')) return null;
        
        $ai_manager = BestwayForms_Integration_AI::instance();
        $result = $ai_manager->process_data($form_data);
        
        if ($result) {
            BestwayForms::log("AI processing completed for lead {$lead_id}");
        } else {
            BestwayForms::log("AI processing failed for lead {$lead_id}", 'error');
        }
        
        return $result;
    }
}
