<?php
/**
 * Plugin Name: Bestway Forms
 * Plugin URI: https://github.com/dartanianfactory/Bestway_Forms_Plugin_Wordpress.git
 * Description: Advanced form management system with n8n, AI and WooCommerce integrations
 * Version: 2.4.2
 * Author: Roman Agafonov
 * Author URI: https://t.me/boontar_mini
 * Text Domain: bestway-forms
 * Domain Path: /
 */

if (!defined('ABSPATH')) exit;

define('BESTWAY_FORMS_VERSION', '2.4.2');
define('BESTWAY_FORMS_PATH', plugin_dir_path(__FILE__));
define('BESTWAY_FORMS_URL', plugin_dir_url(__FILE__));

$plugin_files = [
    // Core
    'includes/core/class-model.php',
    'includes/core/class-view.php',
    'includes/core/class-controller.php',
    
    // Models
    'includes/models/class-model-settings.php',
    'includes/models/class-model-forms.php',
    'includes/models/class-model-leads.php',
    'includes/models/class-model-woocommerce.php',
    
    // Integrations
    'includes/integrations/class-integration-n8n.php',
    'includes/integrations/class-integration-ai.php',
    'includes/integrations/class-integration-smtp.php',
    
    // Controllers
    'includes/controllers/class-controller-admin.php',
    'includes/controllers/class-controller-ajax.php',
    'includes/controllers/class-controller-forms.php',
    'includes/controllers/class-controller-woocommerce.php',
    'includes/controllers/class-controller-exports.php',
    
    // Admin Renders
    'includes/views/admin/render/class-admin-dashboard-render.php',
    'includes/views/admin/render/class-admin-forms-list-render.php',
    'includes/views/admin/render/class-admin-create-form-render.php',
    'includes/views/admin/render/tabs/class-tab-general-render.php',
    'includes/views/admin/render/tabs/class-tab-n8n-render.php',
    'includes/views/admin/render/tabs/class-tab-ai-render.php',
    'includes/views/admin/render/tabs/class-tab-smtp-render.php',
    'includes/views/admin/render/tabs/class-tab-contacts-render.php',
    'includes/views/admin/render/tabs/class-tab-woocommerce-render.php',
    'includes/views/admin/render/class-admin-leads-render.php',
    'includes/views/admin/render/class-admin-history-render.php',
];

foreach ($plugin_files as $file) {
    $file_path = BESTWAY_FORMS_PATH . $file;
    if (file_exists($file_path)) {
        require_once $file_path;
    } else {
        error_log("BestwayForms: File not found - " . $file_path);
    }
}

class BestwayForms {
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('plugins_loaded', [$this, 'init']);
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }
    
    public function init() {
        if (class_exists('BestwayForms_Controller_Admin')) {
            BestwayForms_Controller_Admin::instance();
        }
        
        if (class_exists('BestwayForms_Controller_AJAX')) {
            BestwayForms_Controller_AJAX::instance();
        }
        
        if (class_exists('BestwayForms_Controller_Forms')) {
            BestwayForms_Controller_Forms::instance();
        }
        
        if (class_exists('BestwayForms_Controller_Exports')) {
            BestwayForms_Controller_Exports::instance();
        }

        if (class_exists('BestwayForms_Integration_SMTP')) {
            BestwayForms_Integration_SMTP::instance();
        }
        
        if (class_exists('WooCommerce') && class_exists('BestwayForms_Controller_WooCommerce')) {
            BestwayForms_Controller_WooCommerce::instance();
        }
    }
    
    public static function activate() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $tables = [
            "CREATE TABLE {$wpdb->prefix}gdzl_forms (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                name varchar(100) NOT NULL,
                template varchar(50) NOT NULL,
                settings text NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) $charset_collate;",
            
            "CREATE TABLE {$wpdb->prefix}gdzl_leads (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                form_id mediumint(9) NOT NULL,
                form_data text NOT NULL,
                source varchar(50) DEFAULT 'form',
                n8n_response text,
                ai_processed_data text,
                status varchar(20) DEFAULT 'new',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) $charset_collate;",
            
            "CREATE TABLE {$wpdb->prefix}gdzl_wc_leads (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                order_id bigint(20) NOT NULL,
                customer_data text NOT NULL,
                order_data text NOT NULL,
                n8n_response text,
                ai_processed_data text,
                n8n_sent tinyint(1) DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY order_id (order_id)
            ) $charset_collate;"
        ];
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        foreach ($tables as $sql) {
            dbDelta($sql);
        }

        add_option('bestway_forms_n8n_enabled', '0');
        add_option('bestway_forms_ai_enabled', '0');
        add_option('bestway_forms_wc_enabled', '0');

        $contact_form_dir = BESTWAY_FORMS_PATH . 'forms/contact-form/';
        if (!is_dir($contact_form_dir)) {
            wp_mkdir_p($contact_form_dir);
        }
        
        $contact_form_file = $contact_form_dir . 'form.php';
        if (!file_exists($contact_form_file)) {
            file_put_contents($contact_form_file, '<?php
            if (!defined(\'ABSPATH\')) exit;
            ?>
            <div class="contact-form-template">
                <div class="form-field">
                    <label for="name">Имя *</label>
                    <input type="text" id="name" name="name" required>
                    <div class="error-message"></div>
                </div>
                
                <div class="form-field">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required>
                    <div class="error-message"></div>
                </div>
                
                <div class="form-field">
                    <label for="phone">Телефон</label>
                    <input type="tel" id="phone" name="phone">
                    <div class="error-message"></div>
                </div>
                
                <div class="form-field">
                    <label for="message">Сообщение</label>
                    <textarea id="message" name="message" rows="4"></textarea>
                    <div class="error-message"></div>
                </div>
            </div>');
        }
        
        flush_rewrite_rules();
    }
    
    public static function deactivate() {
        flush_rewrite_rules();
    }
    
    public static function log($message, $type = 'info') {
        if (!defined('WP_DEBUG') || !WP_DEBUG) return;
        
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[{$timestamp}] [{$type}] {$message}" . PHP_EOL;
        
        error_log($log_message);
    }
}

BestwayForms::instance();