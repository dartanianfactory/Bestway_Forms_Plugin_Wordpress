<?php
if (!defined('ABSPATH')) exit;

class BestwayForms_Integration_N8N {
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function send_lead($lead_data) {
        $webhook_url = get_option('bestway_forms_n8n_webhook_url');
        if (!$webhook_url) return false;
        
        $response = wp_remote_post($webhook_url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($lead_data),
            'timeout' => 10
        ]);
        
        return !is_wp_error($response);
    }
}