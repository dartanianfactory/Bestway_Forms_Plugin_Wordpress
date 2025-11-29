<?php
if (!defined('ABSPATH')) exit;

class BestwayForms_Controller_AJAX {
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
        add_action('wp_ajax_bestway_create_form', [$this, 'ajax_create_form']);
        add_action('wp_ajax_submit_bestway_form', [$this, 'handle_form_submission']);
        add_action('wp_ajax_nopriv_submit_bestway_form', [$this, 'handle_form_submission']);
    }
    
    public function ajax_create_form() {
        if (!check_ajax_referer('bestway_forms_nonce', 'nonce', false)) {
            wp_send_json_error('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Неавторизованный доступ');
        }
        
        $template = sanitize_text_field($_POST['template'] ?? '');
        
        if (empty($template)) {
            wp_send_json_error('Шаблон не указан');
        }
        
        $forms_model = new BestwayForms_Model_Forms();
        $form_data = $forms_model->create_form_from_template($template);
        
        if ($form_data && isset($form_data['id'])) {
            BestwayForms::log("Форма создана: ID {$form_data['id']}, шаблон: {$template}");
            wp_send_json_success([
                'form_id' => $form_data['id'],
                'message' => 'Форма успешно создана!',
                'redirect_url' => admin_url('admin.php?page=gadzila-forms-list')
            ]);
        } else {
            BestwayForms::log("Ошибка создания формы из шаблона: {$template}", 'error');
            wp_send_json_error('Ошибка при создании формы из шаблона');
        }
    }
    
    public function handle_form_submission() {
        check_ajax_referer('bestway_forms_submit', 'bestway_nonce');
        
        $form_id = intval($_POST['form_id'] ?? 0);
        
        if (!$form_id) {
            wp_send_json_error('Неверный ID формы');
        }
        
        $errors = $this->validate_form_data($_POST);
        
        if (!empty($errors)) {
            wp_send_json_error($errors);
        }
        
        if (!class_exists('BestwayForms_Model_Leads')) {
            wp_send_json_error('Модель лидов не найдена');
        }
        
        $leads_model = new BestwayForms_Model_Leads();
        $result = $leads_model->process_lead($_POST);
        
        if ($result) {
            BestwayForms::log("Получен новый лид с формы #{$form_id}");
            wp_send_json_success('Форма успешно отправлена! Мы свяжемся с вами в ближайшее время.');
        } else {
            BestwayForms::log("Ошибка обработки лида с формы #{$form_id}", 'error');
            wp_send_json_error('Ошибка отправки формы. Пожалуйста, попробуйте еще раз.');
        }
        
        wp_die();
    }
    
    private function validate_form_data($data) {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = 'Пожалуйста, введите ваше имя';
        }
        
        if (empty($data['email'])) {
            $errors['email'] = 'Пожалуйста, введите ваш email';
        } elseif (!is_email($data['email'])) {
            $errors['email'] = 'Пожалуйста, введите корректный email';
        }

        return $errors;
    }
}
