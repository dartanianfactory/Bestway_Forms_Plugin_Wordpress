<?php
if (!defined('ABSPATH')) exit;

if (!class_exists('BestwayForms_View')) {
    class BestwayForms_View {
        public static function render($template, $data = []) {
            $template_path = BESTWAY_FORMS_PATH . 'views/' . $template . '.php';
            
            if (!file_exists($template_path)) {
                return '';
            }
            
            extract($data);
            ob_start();
            include $template_path;
            return ob_get_clean();
        }
    }
}
