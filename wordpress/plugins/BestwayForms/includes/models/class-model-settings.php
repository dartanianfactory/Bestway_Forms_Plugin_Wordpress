<?php
if (!defined('ABSPATH')) exit;

class BestwayForms_Model_Settings extends BestwayForms_Model {
    public static function is_n8n_enabled() {
        return get_option('bestway_forms_n8n_enabled') === '1';
    }
    
    public static function is_ai_enabled() {
        return get_option('bestway_forms_ai_enabled') === '1';
    }
    
    public static function is_wc_enabled() {
        return get_option('bestway_forms_wc_enabled') === '1';
    }
    
    public static function should_capture_wc_order($order_id) {
        if (!self::is_wc_enabled()) return false;
        
        $capture_all = get_option('bestway_forms_wc_capture_all') === '1';
        
        if ($capture_all) return true;
        
        $order = wc_get_order($order_id);
        return $order && $order->get_status() === 'completed';
    }
    
    public function get_ai_providers() {
        return [
            'openai' => 'OpenAI GPT',
            'claude' => 'Anthropic Claude',
            'gemini' => 'Google Gemini',
            'custom' => 'Custom API'
        ];
    }
    
    public function get_data_sources() {
        return [
            'forms' => 'Forms Data',
            'woocommerce' => 'WooCommerce Orders',
            'all' => 'All Data',
            'no_ai' => 'No AI Processing'
        ];
    }
}
