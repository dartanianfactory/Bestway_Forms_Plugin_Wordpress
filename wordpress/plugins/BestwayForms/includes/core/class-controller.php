<?php
if (!defined('ABSPATH')) exit;

abstract class BestwayForms_Controller {
    protected $model;
    protected $view;
    
    public function __construct() {
        $this->setup_components();
        $this->register_hooks();
    }
    
    abstract protected function setup_components();
    abstract protected function register_hooks();
    
    protected function load_model($model_name) {
        $model_class = 'BestwayForms_Model_' . $model_name;
        if (class_exists($model_class)) {
            return new $model_class();
        }
        return null;
    }
    
    protected function load_view($template, $data = []) {
        return BestwayForms_View::render($template, $data);
    }
}
