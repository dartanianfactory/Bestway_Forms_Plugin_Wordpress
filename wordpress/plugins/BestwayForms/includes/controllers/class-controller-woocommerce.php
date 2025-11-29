<?php
if (!defined('ABSPATH')) exit;

class BestwayForms_Controller_WooCommerce {
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
        add_action('woocommerce_new_order', [$this, 'capture_order'], 10, 2);
        add_action('woocommerce_order_status_changed', [$this, 'handle_order_status'], 10, 4);
        add_action('wp_ajax_test_wc_integration', [$this, 'test_integration']);
    }
    
    public function capture_order($order_id, $order = null) {
        if (!$order) {
            $order = wc_get_order($order_id);
        }
        
        if ($order && BestwayForms_Model_Settings::should_capture_wc_order($order_id)) {
            $wc_model = new BestwayForms_Model_WooCommerce();
            $wc_model->process_order($order_id);
        }
    }
    
    public function handle_order_status($order_id, $old_status, $new_status, $order) {
        $wc_model = new BestwayForms_Model_WooCommerce();
        $wc_model->update_order_status($order_id, $new_status);
    }
    
    public function test_integration() {
        check_ajax_referer('bestway_forms_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        if (!class_exists('WooCommerce')) {
            wp_send_json_error('WooCommerce is not active');
        }
        
        wp_send_json_success('WooCommerce integration is working correctly');
    }
}
