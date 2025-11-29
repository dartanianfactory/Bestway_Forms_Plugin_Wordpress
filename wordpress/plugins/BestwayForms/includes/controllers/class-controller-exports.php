<?php
if (!defined('ABSPATH')) exit;

class BestwayForms_Controller_Exports {
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        $this->register_hooks();
    }
    
    private function register_hooks() {
        add_action('wp_ajax_export_leads_csv', [$this, 'export_leads_csv']);
        add_action('wp_ajax_export_leads_excel', [$this, 'export_leads_excel']);
        add_action('wp_ajax_export_leads_json', [$this, 'export_leads_json']);
        add_action('wp_ajax_export_leads_page', [$this, 'export_leads_page']);
    }
    
    public function export_leads_csv() {
        error_log('=== EXPORT CSV STARTED ===');
        $this->check_export_permissions();
        $leads = $this->get_all_leads_data();
        
        error_log('Leads count: ' . count($leads));
        
        if (empty($leads)) {
            wp_die('Нет данных для экспорта');
        }
        
        $this->send_csv_headers('leads.csv');
        $this->generate_csv($leads);
        exit;
    }
    
    public function export_leads_excel() {
        error_log('=== EXPORT EXCEL STARTED ===');
        $this->check_export_permissions();
        $leads = $this->get_all_leads_data();
        
        error_log('Leads count: ' . count($leads));
        
        if (empty($leads)) {
            wp_die('Нет данных для экспорта');
        }
        
        $this->send_excel_headers('leads.xlsx');
        $this->generate_excel($leads);
        exit;
    }
    
    public function export_leads_json() {
        error_log('=== EXPORT JSON STARTED ===');
        $this->check_export_permissions();
        $leads = $this->get_all_leads_data();
        
        error_log('Leads count: ' . count($leads));
        
        if (empty($leads)) {
            wp_die('Нет данных для экспорта');
        }
        
        $this->send_json_headers('leads.json');
        echo wp_json_encode($leads, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    public function export_leads_page() {
        $this->check_export_permissions();
        
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 1000;
        
        $leads = $this->get_paginated_leads($page, $per_page);
        $total = $this->get_total_leads_count();
        
        wp_send_json_success([
            'leads' => $leads,
            'pagination' => [
                'page' => $page,
                'per_page' => $per_page,
                'total' => $total,
                'total_pages' => ceil($total / $per_page)
            ]
        ]);
    }
    
    private function check_export_permissions() {
        error_log('Checking permissions...');
        error_log('Current user can manage_options: ' . (current_user_can('manage_options') ? 'YES' : 'NO'));
        error_log('Nonce from request: ' . ($_GET['nonce'] ?? 'MISSING'));
        error_log('Nonce valid: ' . (wp_verify_nonce($_GET['nonce'] ?? '', 'export_leads') ? 'YES' : 'NO'));
        
        if (!current_user_can('manage_options')) {
            error_log('PERMISSION DENIED: User cannot manage_options');
            status_header(403);
            wp_die('Недостаточно прав для экспорта');
        }
        
        if (!wp_verify_nonce($_GET['nonce'] ?? '', 'export_leads')) {
            error_log('PERMISSION DENIED: Invalid nonce');
            status_header(403);
            wp_die('Ошибка безопасности');
        }
        
        error_log('Permissions OK');
    }
    
    private function get_all_leads_data() {
        error_log('Getting all leads data...');
        $leads_model = new BestwayForms_Model_Leads();
        $all_leads = $leads_model->get_all_leads();
        $export_data = [];
        
        error_log('Total leads found: ' . count($all_leads));
        
        foreach ($all_leads as $lead) {
            $export_data[] = $this->prepare_lead_for_export($lead);
        }
        
        error_log('Export data prepared: ' . count($export_data) . ' items');
        return $export_data;
    }
    
    private function get_paginated_leads($page, $per_page) {
        $leads_model = new BestwayForms_Model_Leads();
        $all_leads = $leads_model->get_all_leads();
        $export_data = [];
        
        $offset = ($page - 1) * $per_page;
        $paginated_leads = array_slice($all_leads, $offset, $per_page);
        
        foreach ($paginated_leads as $lead) {
            $export_data[] = $this->prepare_lead_for_export($lead);
        }
        
        return $export_data;
    }
    
    private function get_total_leads_count() {
        $leads_model = new BestwayForms_Model_Leads();
        $all_leads = $leads_model->get_all_leads();
        return count($all_leads);
    }
    
    private function prepare_lead_for_export($lead) {
        $is_wc_lead = (strpos($lead->id, 'wc_') === 0) || isset($lead->order_id);
        
        if ($is_wc_lead) {
            return $this->prepare_wc_lead_for_export($lead);
        } else {
            return $this->prepare_form_lead_for_export($lead);
        }
    }
    
    private function prepare_form_lead_for_export($lead) {
        $forms_model = new BestwayForms_Model_Forms();
        $form = $forms_model->get_form($lead->form_id);
        $form_data = json_decode($lead->form_data, true);
        
        $export_data = [
            'id' => $lead->id,
            'type' => 'form',
            'source' => $form ? $form->name : 'Form #' . $lead->form_id,
            'status' => $lead->status,
            'created_at' => $lead->created_at
        ];

        if ($form) {
            $form_settings = json_decode($form->settings, true);
            $form_fields = $form_settings['fields'] ?? [];
            
            foreach ($form_fields as $field_name => $field_config) {
                $label = $field_config['label'] ?? ucfirst(str_replace('_', ' ', $field_name));
                $export_data[$label] = $form_data[$field_name] ?? '';
            }
        } else {
            foreach ($form_data as $key => $value) {
                if (!in_array($key, ['form_id', 'action', 'bestway_nonce'])) {
                    $export_data[ucfirst($key)] = $value;
                }
            }
        }

        if ($lead->n8n_response) {
            $n8n_data = json_decode($lead->n8n_response, true);
            $export_data['n8n_processed'] = $n8n_data ? 'Yes' : 'No';
        }
        
        if ($lead->ai_processed_data) {
            $ai_data = json_decode($lead->ai_processed_data, true);
            $export_data['ai_processed'] = $ai_data ? 'Yes' : 'No';
        }
        
        return $export_data;
    }
    
    private function prepare_wc_lead_for_export($lead) {
        $customer_data = json_decode($lead->form_data ?? $lead->customer_data, true);
        $order_data = isset($lead->order_data) ? json_decode($lead->order_data, true) : [];
        
        $export_data = [
            'id' => $lead->id,
            'type' => 'woocommerce',
            'source' => 'WooCommerce Order #' . ($lead->order_id ?? 'N/A'),
            'status' => $lead->status ?? 'new',
            'created_at' => $lead->created_at
        ];
        
        // Основные данные клиента
        $export_data['customer_name'] = trim(($customer_data['first_name'] ?? '') . ' ' . ($customer_data['last_name'] ?? ''));
        $export_data['customer_email'] = $customer_data['email'] ?? '';
        $export_data['customer_phone'] = $customer_data['phone'] ?? '';
        
        // Данные заказа
        $export_data['order_id'] = $order_data['order_id'] ?? $lead->order_id ?? '';
        $export_data['order_total'] = $order_data['order_total'] ?? '';
        $export_data['order_status'] = $order_data['order_status'] ?? '';
        $export_data['payment_method'] = $order_data['payment_method_title'] ?? $order_data['payment_method'] ?? '';
        
        // Товары
        if (isset($order_data['items']) && is_array($order_data['items'])) {
            $products = [];
            foreach ($order_data['items'] as $item) {
                $products[] = ($item['product_name'] ?? $item['name'] ?? 'N/A') . ' (x' . ($item['quantity'] ?? '0') . ')';
            }
            $export_data['products'] = implode('; ', $products);
        }
        
        // Адрес
        if (isset($customer_data['address'])) {
            $address = $customer_data['address'];
            $export_data['address'] = implode(', ', array_filter([
                $address['address_1'] ?? '',
                $address['address_2'] ?? '',
                $address['city'] ?? '',
                $address['state'] ?? '',
                $address['postcode'] ?? '',
                $address['country'] ?? ''
            ]));
        }
        
        // Данные интеграций
        if ($lead->n8n_response) {
            $n8n_data = json_decode($lead->n8n_response, true);
            $export_data['n8n_processed'] = $n8n_data ? 'Yes' : 'No';
        }
        
        if ($lead->ai_processed_data) {
            $ai_data = json_decode($lead->ai_processed_data, true);
            $export_data['ai_processed'] = $ai_data ? 'Yes' : 'No';
        }
        
        return $export_data;
    }
    
    private function send_csv_headers($filename) {
        error_log('Sending CSV headers...');
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        error_log('CSV headers sent');
    }
    
    private function send_excel_headers($filename) {
        error_log('Sending Excel headers...');
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        error_log('Excel headers sent');
    }
    
    private function send_json_headers($filename) {
        error_log('Sending JSON headers...');
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        error_log('JSON headers sent');
    }
    
    private function generate_csv($data) {
        error_log('Generating CSV...');
        $output = fopen('php://output', 'w');
        
        // Добавляем BOM для корректного отображения кириллицы в Excel
        fwrite($output, "\xEF\xBB\xBF");
        
        if (!empty($data)) {
            $headers = array_keys($data[0]);
            fputcsv($output, $headers, ';');
            
            foreach ($data as $row) {
                fputcsv($output, $row, ';');
            }
        }
        
        fclose($output);
        error_log('CSV generation completed');
    }
    
    private function generate_excel($data) {
        error_log('Generating Excel (fallback to CSV)...');
        $this->generate_csv($data);
    }
}