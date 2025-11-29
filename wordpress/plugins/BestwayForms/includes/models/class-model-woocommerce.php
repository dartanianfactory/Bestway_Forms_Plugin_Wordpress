<?php
if (!defined('ABSPATH'))
    exit;

class BestwayForms_Model_WooCommerce extends BestwayForms_Model
{
    protected $table_name = 'gdzl_wc_leads';

    public function get_all_wc_leads($page = 1, $per_page = 50) {
        global $wpdb;
        
        $offset = ($page - 1) * $per_page;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->get_table_name()} ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));
    }

    public function get_orders_count()
    {
        global $wpdb;
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->get_table_name()}");
        return $count ?: 0;
    }

    private function extract_customer_data($order)
    {
        return [
            'first_name' => $order->get_billing_first_name(),
            'last_name' => $order->get_billing_last_name(),
            'email' => $order->get_billing_email(),
            'phone' => $order->get_billing_phone(),
            'address' => [
                'address_1' => $order->get_billing_address_1(),
                'address_2' => $order->get_billing_address_2(),
                'city' => $order->get_billing_city(),
                'state' => $order->get_billing_state(),
                'postcode' => $order->get_billing_postcode(),
                'country' => $order->get_billing_country()
            ]
        ];
    }

    private function extract_order_data($order)
    {
        return [
            'order_id' => $order->get_id(),
            'order_number' => $order->get_order_number(),
            'order_date' => $order->get_date_created()->format('Y-m-d H:i:s'),
            'order_status' => $order->get_status(),
            'order_total' => $order->get_total(),
            'order_currency' => $order->get_currency(),
            'payment_method' => $order->get_payment_method(),
            'payment_method_title' => $order->get_payment_method_title(),
            'items' => $this->extract_order_items($order)
        ];
    }

    private function extract_order_items($order)
    {
        $items = [];
        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            $items[] = [
                'product_id' => $item->get_product_id(),
                'product_name' => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'subtotal' => $item->get_subtotal(),
                'total' => $item->get_total()
            ];
        }
        return $items;
    }

    public function process_order($order_id)
    {
        global $wpdb;

        $order = wc_get_order($order_id);
        if (!$order)
            return false;

        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->get_table_name()} WHERE order_id = %d",
            $order_id
        ));

        if ($existing)
            return true;

        $customer_data = $this->extract_customer_data($order);
        $order_data = $this->extract_order_data($order);

        $result = $wpdb->insert(
            $this->get_table_name(),
            [
                'order_id' => $order_id,
                'customer_data' => json_encode($customer_data),
                'order_data' => json_encode($order_data)
            ],
            ['%d', '%s', '%s']
        );

        if ($result) {
            $wc_lead_id = $wpdb->insert_id;

            $n8n_result = $this->send_to_n8n($wc_lead_id, $order_id, $customer_data, $order_data);
            $ai_result = $this->process_with_ai($wc_lead_id, $order_id, $customer_data, $order_data);

            $update_data = [];
            if ($n8n_result) {
                $update_data['n8n_response'] = json_encode($n8n_result);
                $update_data['n8n_sent'] = 1;
            }
            if ($ai_result) {
                $update_data['ai_processed_data'] = json_encode($ai_result);
            }

            if (!empty($update_data)) {
                $wpdb->update(
                    $this->get_table_name(),
                    $update_data,
                    ['id' => $wc_lead_id],
                    ['%s', '%d', '%s'],
                    ['%d']
                );
            }

            BestwayForms::log("WooCommerce lead created: ID {$wc_lead_id}, order: {$order_id}");
            return $wc_lead_id;
        }

        return false;
    }

    private function send_to_n8n($wc_lead_id, $order_id, $customer_data, $order_data)
    {
        if (!get_option('bestway_forms_n8n_enabled'))
            return null;

        $webhook_url = get_option('bestway_forms_n8n_webhook_url');
        if (!$webhook_url)
            return null;

        $api_key = get_option('bestway_forms_n8n_api_key');

        $headers = ['Content-Type' => 'application/json'];
        if ($api_key) {
            $headers['Authorization'] = 'Bearer ' . $api_key;
        }

        $payload = [
            'wc_lead_id' => $wc_lead_id,
            'order_id' => $order_id,
            'customer_data' => $customer_data,
            'order_data' => $order_data,
            'source' => 'woocommerce',
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
                BestwayForms::log("n8n response received for WooCommerce lead {$wc_lead_id}");
                return $json_response;
            }

            BestwayForms::log("n8n raw response for WooCommerce lead {$wc_lead_id}: " . $body);
            return ['raw_response' => $body];
        }

        BestwayForms::log("n8n error for WooCommerce lead {$wc_lead_id}: " . $response->get_error_message(), 'error');
        return null;
    }

    private function process_with_ai($wc_lead_id, $order_id, $customer_data, $order_data)
    {
        if (!get_option('bestway_forms_ai_enabled'))
            return null;

        $data_source = get_option('bestway_forms_ai_data_source');
        if (!in_array($data_source, ['woocommerce', 'all']))
            return null;

        $ai_manager = BestwayForms_Integration_AI::instance();
        $result = $ai_manager->process_data([
            'type' => 'woocommerce_order',
            'order_id' => $order_id,
            'customer_data' => $customer_data,
            'order_data' => $order_data
        ]);

        if ($result) {
            BestwayForms::log("AI processing completed for WooCommerce lead {$wc_lead_id}");
        } else {
            BestwayForms::log("AI processing failed for WooCommerce lead {$wc_lead_id}", 'error');
        }

        return $result;
    }

    public function update_order_status($order_id, $status)
    {
    }
}
