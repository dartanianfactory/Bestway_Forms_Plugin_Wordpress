<?php
if (!defined('ABSPATH')) exit;

class BestwayForms_Controller_Admin {
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
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'admin_scripts']);
    }
    
    public function admin_menu() {
        add_menu_page(
            'BestwayForms',
            'BestwayForms',
            'manage_options',
            'bestway-forms',
            [$this, 'dashboard_page'],
            'dashicons-email-alt',
            30
        );
        
        add_submenu_page(
            'bestway-forms',
            'Дашборд',
            'Дашборд',
            'manage_options',
            'bestway-forms',
            [$this, 'dashboard_page']
        );
        
        add_submenu_page(
            'bestway-forms',
            'Формы',
            'Формы',
            'manage_options',
            'bestway-forms-list',
            [$this, 'forms_list_page']
        );
        
        add_submenu_page(
            'bestway-forms',
            'Создать форму',
            'Создать форму',
            'manage_options',
            'bestway-forms-create',
            [$this, 'create_form_page']
        );
        
        add_submenu_page(
            'bestway-forms',
            'Лиды',
            'Лиды',
            'manage_options',
            'bestway-forms-leads',
            [$this, 'leads_page']
        );
        
        add_submenu_page(
            'bestway-forms',
            'История',
            'История',
            'manage_options',
            'bestway-forms-history',
            [$this, 'history_page']
        );
        
        add_submenu_page(
            'bestway-forms',
            'Настройки',
            'Настройки',
            'manage_options',
            'bestway-forms-settings',
            [$this, 'settings_page']
        );
    }
    
    public function register_settings() {
        register_setting('bestway_forms_general', 'bestway_forms_n8n_enabled');
        register_setting('bestway_forms_general', 'bestway_forms_ai_enabled');
        register_setting('bestway_forms_general', 'bestway_forms_wc_enabled');
        
        register_setting('bestway_forms_n8n', 'bestway_forms_n8n_webhook_url');
        register_setting('bestway_forms_n8n', 'bestway_forms_n8n_api_key');
        
        register_setting('bestway_forms_ai', 'bestway_forms_ai_provider');
        register_setting('bestway_forms_ai', 'bestway_forms_ai_api_key');
        register_setting('bestway_forms_ai', 'bestway_forms_ai_custom_url');
        register_setting('bestway_forms_ai', 'bestway_forms_ai_data_source');
        register_setting('bestway_forms_ai', 'bestway_forms_ai_system_prompt');
        register_setting('bestway_forms_ai', 'bestway_forms_ai_analysis_prompt');
        register_setting('bestway_forms_ai', 'bestway_forms_ai_response_prompt');
        
        register_setting('bestway_forms_wc', 'bestway_forms_wc_capture_all');
        register_setting('bestway_forms_wc', 'bestway_forms_wc_statuses');

        register_setting('bestway_forms_smtp', 'bestway_forms_smtp_host');
        register_setting('bestway_forms_smtp', 'bestway_forms_smtp_port');
        register_setting('bestway_forms_smtp', 'bestway_forms_smtp_encryption');
        register_setting('bestway_forms_smtp', 'bestway_forms_smtp_username');
        register_setting('bestway_forms_smtp', 'bestway_forms_smtp_password');
        register_setting('bestway_forms_smtp', 'bestway_forms_smtp_from_email');
        register_setting('bestway_forms_smtp', 'bestway_forms_smtp_from_name');
        register_setting('bestway_forms_smtp', 'bestway_forms_smtp_notifications_enabled');
        register_setting('bestway_forms_smtp', 'bestway_forms_smtp_lead_types');
        register_setting('bestway_forms_smtp', 'bestway_forms_smtp_recipient_emails');
        register_setting('bestway_forms_smtp', 'bestway_forms_smtp_email_subject');
    }
        
    public function admin_scripts($hook) {
        if (strpos($hook, 'bestway-forms') === false) return;
        
        wp_enqueue_script('jquery');
        wp_enqueue_style(
            'bestway-forms-admin',
            BESTWAY_FORMS_URL . 'assets/css/admin.css',
            [],
            BESTWAY_FORMS_VERSION
        );
        
        wp_enqueue_script(
            'bestway-forms-admin',
            BESTWAY_FORMS_URL . 'assets/js/admin.js',
            ['jquery'],
            BESTWAY_FORMS_VERSION,
            true
        );
        
        wp_localize_script('bestway-forms-admin', 'bestway_forms_admin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bestway_forms_nonce')
        ]);
    }
    
    public function dashboard_page() {
        $forms_model = new BestwayForms_Model_Forms();
        $leads_model = new BestwayForms_Model_Leads();
        
        $stats = [
            'total_forms' => 0,
            'total_leads' => 0,
            'form_leads' => 0,
            'wc_leads' => 0,
            'new_leads' => 0,
            'recent_leads' => []
        ];
        
        try {
            $stats['total_forms'] = $forms_model->get_forms_count();
            $stats['total_leads'] = $leads_model->get_leads_count();
            $stats['form_leads'] = $leads_model->get_leads_count_by_source('form');
            $stats['new_leads'] = $leads_model->get_leads_count_by_status('new');
            $stats['recent_leads'] = $leads_model->get_recent_leads(10);
            
            if (class_exists('BestwayForms_Model_WooCommerce')) {
                $wc_model = new BestwayForms_Model_WooCommerce();
                $stats['wc_leads'] = $wc_model->get_orders_count();
            }
        } catch (Exception $e) {
            echo '<div class="error"><p>Ошибка загрузки статистики: ' . esc_html($e->getMessage()) . '</p></div>';
        }
        
        BestwayForms_Admin_Dashboard_Render::render($stats);
    }
    
    public function forms_list_page() {
        $forms_model = new BestwayForms_Model_Forms();
        $forms = $forms_model->get_all_forms();
        
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['form_id']) && isset($_GET['_wpnonce'])) {
            $this->handle_form_delete();
        }
        
        BestwayForms_Admin_Forms_List_Render::render($forms);
    }
    
    public function create_form_page() {
        $forms_model = new BestwayForms_Model_Forms();
        $templates = $forms_model->get_form_templates();
        
        $edit_form_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
        $edit_form = $edit_form_id ? $forms_model->get_form($edit_form_id) : null;
        
        BestwayForms_Admin_Create_Form_Render::render($templates, $edit_form);
    }
    
    public function leads_page() {
        $leads_model = new BestwayForms_Model_Leads();
        
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 50;
        
        $leads = $leads_model->get_all_leads($current_page, $per_page);
        $total_leads = $leads_model->get_total_leads_count();
        $total_pages = ceil($total_leads / $per_page);
        
        BestwayForms_Admin_Leads_Render::render($leads, $current_page, $total_pages, $total_leads);
    }

    public function history_page() {
        $leads_model = new BestwayForms_Model_Leads();
        
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 50;
        
        $history = $leads_model->get_processing_history($current_page, $per_page);
        $total_history = $leads_model->get_total_history_count();
        $total_pages = ceil($total_history / $per_page);
        
        BestwayForms_Admin_History_Render::render($history, $current_page, $total_pages, $total_history);
    }
    
    public function settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die('У вас недостаточно прав для доступа к этой странице.');
        }

        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        ?>
        <div class="wrap bestway-forms-settings">
            <h1>Настройки BestwayForms</h1>
            
            <nav class="nav-tab-wrapper">
                <a href="<?php echo admin_url('admin.php?page=bestway-forms-settings&tab=general'); ?>" 
                class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    Общие настройки
                </a>
                <a href="<?php echo admin_url('admin.php?page=bestway-forms-settings&tab=n8n'); ?>" 
                class="nav-tab <?php echo $active_tab === 'n8n' ? 'nav-tab-active' : ''; ?>">
                    n8n Интеграция
                </a>
                <a href="<?php echo admin_url('admin.php?page=bestway-forms-settings&tab=ai'); ?>" 
                class="nav-tab <?php echo $active_tab === 'ai' ? 'nav-tab-active' : ''; ?>">
                    AI Менеджер
                </a>
                <a href="<?php echo admin_url('admin.php?page=bestway-forms-settings&tab=woocommerce'); ?>" 
                class="nav-tab <?php echo $active_tab === 'woocommerce' ? 'nav-tab-active' : ''; ?>">
                    WooCommerce
                </a>
                <a href="<?php echo admin_url('admin.php?page=bestway-forms-settings&tab=smtp'); ?>" 
                class="nav-tab <?php echo $active_tab === 'smtp' ? 'nav-tab-active' : ''; ?>">
                    SMTP & Email
                </a>
                <a href="<?php echo admin_url('admin.php?page=bestway-forms-settings&tab=contacts'); ?>" 
                class="nav-tab <?php echo $active_tab === 'contacts' ? 'nav-tab-active' : ''; ?>">
                    Контакты
                </a>
            </nav>
            
            <div class="bestway-settings-content">
                <?php 
                switch ($active_tab) {
                    case 'n8n':
                        BestwayForms_Tab_N8N_Render::render();
                        break;
                    case 'ai':
                        BestwayForms_Tab_AI_Render::render();
                        break;
                    case 'woocommerce':
                        BestwayForms_Tab_WooCommerce_Render::render();
                        break;
                    case 'smtp':
                        BestwayForms_Tab_SMTP_Render::render();
                        break;
                    case 'contacts':
                        BestwayForms_Tab_Contacts_Render::render();
                        break;
                    case 'general':
                    default:
                        BestwayForms_Tab_General_Render::render();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    public function handle_form_delete() {
        if (!isset($_GET['form_id']) || !isset($_GET['_wpnonce'])) {
            wp_die('Неверные параметры');
        }
        
        $form_id = intval($_GET['form_id']);
        $nonce = $_GET['_wpnonce'];
        
        if (!wp_verify_nonce($nonce, 'delete_form_' . $form_id)) {
            wp_die('Ошибка безопасности');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Недостаточно прав');
        }
        
        $forms_model = new BestwayForms_Model_Forms();
        $result = $forms_model->delete_form($form_id);
        
        if ($result) {
            wp_redirect(admin_url('admin.php?page=bestway-forms-list&deleted=1'));
        } else {
            wp_redirect(admin_url('admin.php?page=bestway-forms-list&error=1'));
        }
        exit;
    }
}
