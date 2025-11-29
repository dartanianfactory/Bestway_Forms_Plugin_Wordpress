<?php
if (!defined('ABSPATH')) exit;

class BestwayForms_Controller_Forms {
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
        add_shortcode('bestway_form', [$this, 'render_form']);
        add_action('wp_enqueue_scripts', [$this, 'frontend_scripts']);
    }
    
    public function frontend_scripts() {
        wp_enqueue_style(
            'gadzila-forms-frontend',
            BESTWAY_FORMS_URL . 'assets/css/frontend.css',
            [],
            BESTWAY_FORMS_VERSION
        );
        
        wp_enqueue_script(
            'gadzila-forms-frontend',
            BESTWAY_FORMS_URL . 'assets/js/frontend.js',
            ['jquery'],
            BESTWAY_FORMS_VERSION,
            true
        );
        
        wp_localize_script('gadzila-forms-frontend', 'bestway_forms_frontend', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bestway_forms_submit')
        ]);
    }
    
    public function render_form($atts) {
        $form_id = $atts['id'] ?? 0;
        
        if (!$form_id) {
            return '<p>Ошибка: не указан ID формы</p>';
        }
        
        $forms_model = new BestwayForms_Model_Forms();
        $form = $forms_model->get_form($form_id);
        
        if (!$form) {
            return '<p>Ошибка: форма не найдена</p>';
        }

        $form_settings = json_decode($form->settings, true);
        $fields = $form_settings['fields'] ?? [];
        
        ob_start();

        echo '<div class="gadzila-form-wrapper" data-form-id="' . $form_id . '">';
        echo '<form class="gadzila-form" id="gadzila-form-' . $form_id . '" method="post">';
        echo '<input type="hidden" name="form_id" value="' . $form_id . '">';
        echo '<input type="hidden" name="action" value="submit_bestway_form">';
        echo '<input type="hidden" name="bestway_nonce" value="' . wp_create_nonce('bestway_forms_submit') . '">';
        
        if (!empty($fields)) {
            $this->render_auto_form($fields);
        } else {
            $template_file = BESTWAY_FORMS_PATH . "forms/{$form->template}/form.php";
            if (file_exists($template_file)) {
                include $template_file;
            } else {
                echo '<p>Ошибка: шаблон формы не найден</p>';
            }
        }
        
        echo '<div class="form-field">';
        echo '<button type="submit" class="gadzila-submit-btn">Отправить</button>';
        echo '</div>';
        
        echo '</form>';
        echo '<div class="gadzila-form-messages" style="display: none;"></div>';
        echo '</div>';
        
        return ob_get_clean();
    }
    
    private function render_auto_form($fields) {
        foreach ($fields as $field_name => $field_config) {
            $field_type = $field_config['type'] ?? 'text';
            $field_label = $field_config['label'] ?? ucfirst(str_replace('_', ' ', $field_name));
            $required = isset($field_config['required']) && $field_config['required'] ? 'required' : '';
            $placeholder = isset($field_config['placeholder']) ? 'placeholder="' . esc_attr($field_config['placeholder']) . '"' : '';
            
            echo '<div class="form-field">';
            echo '<label for="' . esc_attr($field_name) . '">' . esc_html($field_label) . '</label>';
            
            switch ($field_type) {
                case 'textarea':
                    echo '<textarea id="' . esc_attr($field_name) . '" name="' . esc_attr($field_name) . '" ' . $required . ' ' . $placeholder . '></textarea>';
                    break;
                case 'select':
                    echo '<select id="' . esc_attr($field_name) . '" name="' . esc_attr($field_name) . '" ' . $required . '>';
                    if (isset($field_config['options'])) {
                        foreach ($field_config['options'] as $option_value => $option_label) {
                            echo '<option value="' . esc_attr($option_value) . '">' . esc_html($option_label) . '</option>';
                        }
                    }
                    echo '</select>';
                    break;
                case 'checkbox':
                    echo '<input type="checkbox" id="' . esc_attr($field_name) . '" name="' . esc_attr($field_name) . '" value="1">';
                    break;
                case 'email':
                    echo '<input type="email" id="' . esc_attr($field_name) . '" name="' . esc_attr($field_name) . '" ' . $required . ' ' . $placeholder . '>';
                    break;
                case 'tel':
                    echo '<input type="tel" id="' . esc_attr($field_name) . '" name="' . esc_attr($field_name) . '" ' . $required . ' ' . $placeholder . '>';
                    break;
                default:
                    echo '<input type="text" id="' . esc_attr($field_name) . '" name="' . esc_attr($field_name) . '" ' . $required . ' ' . $placeholder . '>';
                    break;
            }
            
            echo '<div class="error-message"></div>';
            echo '</div>';
        }
    }
}
