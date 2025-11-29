<?php
if (!defined('ABSPATH')) exit;

class BestwayForms_Model_Forms extends BestwayForms_Model {
    protected $table_name = 'gdzl_forms';
    
    public function get_forms_count() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$this->get_table_name()}") ?: 0;
    }
    
    public function get_form_templates() {
        $templates_path = BESTWAY_FORMS_PATH . 'forms/';
        $templates = [];
        
        if (!is_dir($templates_path)) {
            error_log("BestwayForms: Templates directory not found: " . $templates_path);
            return $templates;
        }
        
        $folders = scandir($templates_path);
        
        foreach ($folders as $folder) {
            if ($folder === '.' || $folder === '..') continue;
            
            $template_file = $templates_path . $folder . '/form.php';
            if (is_dir($templates_path . $folder) && file_exists($template_file)) {
                $templates[$folder] = ucfirst(str_replace('-', ' ', $folder));
            }
        }
        
        return $templates;
    }
    
    public function get_template_fields($template) {
        $template_file = BESTWAY_FORMS_PATH . "forms/{$template}/form.php";
        
        if (!file_exists($template_file)) {
            return [];
        }
        
        return $this->extract_fields_from_template($template_file);
    }
    
    public function create_form_from_template($template) {
        global $wpdb;
        
        $template_data = $this->get_template_data($template);
        if (!$template_data) {
            return false;
        }
        
        $existing_form = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->get_table_name()} WHERE name = %s AND template = %s",
            $template_data['name'],
            $template
        ));
        
        if ($existing_form) {
            BestwayForms::log("Form already exists: ID {$existing_form}, template: {$template}");
            return [
                'id' => $existing_form,
                'name' => $template_data['name'],
                'template' => $template
            ];
        }
        
        $form_data = [
            'name' => $template_data['name'],
            'template' => $template,
            'settings' => json_encode($template_data['settings']),
            'created_at' => current_time('mysql')
        ];

        $result = $wpdb->insert(
            $this->get_table_name(),
            $form_data,
            ['%s', '%s', '%s', '%s']
        );
        
        if ($result === false) {
            BestwayForms::log("Database error creating form: " . $wpdb->last_error, 'error');
            return false;
        }
        
        $form_id = $wpdb->insert_id;
        
        BestwayForms::log("Form created successfully: ID {$form_id}, template: {$template}");
        
        return [
            'id' => $form_id, 
            'name' => $template_data['name'],
            'template' => $template
        ];
    }
    
    private function get_template_data($template) {
        $template_file = BESTWAY_FORMS_PATH . "forms/{$template}/form.php";
        
        if (!file_exists($template_file)) {
            BestwayForms::log("Template file not found: {$template_file}", 'error');
            return false;
        }

        $fields = $this->extract_fields_from_template($template_file);
        
        return [
            'name' => ucfirst(str_replace('-', ' ', $template)) . ' Form',
            'settings' => [
                'fields' => $fields,
                'template' => $template,
                'created' => current_time('mysql')
            ]
        ];
    }
    
    private function extract_fields_from_template($template_file) {
        $content = file_get_contents($template_file);
        $fields = [];
        
        preg_match_all('/<(input|textarea|select)[^>]*name=[\'"]([^\'"]+)[\'"][^>]*>/i', $content, $matches);
        
        if (!empty($matches[2])) {
            foreach ($matches[2] as $index => $field_name) {
                $field_tag = $matches[1][$index];
                $field_html = $matches[0][$index];
                
                $field_config = [
                    'type' => 'text',
                    'label' => ucfirst(str_replace('_', ' ', $field_name)),
                    'required' => false
                ];
                
                if ($field_tag === 'textarea') {
                    $field_config['type'] = 'textarea';
                } elseif ($field_tag === 'select') {
                    $field_config['type'] = 'select';
                    $field_config['options'] = $this->extract_select_options($content, $field_name);
                } else {
                    preg_match('/type=[\'"]([^\'"]+)[\'"]/i', $field_html, $type_match);
                    if (!empty($type_match[1])) {
                        $field_config['type'] = $type_match[1];
                    }
                }
                
                if (strpos($field_html, 'required') !== false) {
                    $field_config['required'] = true;
                }
                
                preg_match('/placeholder=[\'"]([^\'"]+)[\'"]/i', $field_html, $placeholder_match);
                if (!empty($placeholder_match[1])) {
                    $field_config['placeholder'] = $placeholder_match[1];
                }
                
                $fields[$field_name] = $field_config;
            }
        }
        
        return $fields;
    }
    
    private function extract_select_options($content, $field_name) {
        $options = [];
        preg_match('/<select[^>]*name=[\'"]' . preg_quote($field_name, '/') . '[\'"][^>]*>(.*?)<\/select>/is', $content, $select_match);
        
        if (!empty($select_match[1])) {
            preg_match_all('/<option[^>]*value=[\'"]([^\'"]*)[\'"][^>]*>([^<]*)<\/option>/i', $select_match[1], $option_matches);
            
            if (!empty($option_matches[1])) {
                foreach ($option_matches[1] as $index => $value) {
                    $options[$value] = $option_matches[2][$index];
                }
            }
        }
        
        return $options;
    }
    
    public function get_form($form_id) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->get_table_name()} WHERE id = %d", $form_id)
        );
    }
    
    public function get_all_forms() {
        global $wpdb;
        
        return $wpdb->get_results("SELECT * FROM {$this->get_table_name()} ORDER BY created_at DESC");
    }
    
    public function delete_form($form_id) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->get_table_name(),
            ['id' => $form_id],
            ['%d']
        );
        
        if ($result) {
            BestwayForms::log("Form deleted: ID {$form_id}");
        } else {
            BestwayForms::log("Error deleting form: ID {$form_id}", 'error');
        }
        
        return $result;
    }
}
