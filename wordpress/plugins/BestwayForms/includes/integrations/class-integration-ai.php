<?php
if (!defined('ABSPATH')) exit;

class BestwayForms_Integration_AI {
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function process_data($data) {
        $provider = get_option('bestway_forms_ai_provider', 'openai');
        $api_key = get_option('bestway_forms_ai_api_key');
        
        if (!$api_key) {
            BestwayForms::log('AI API key not set', 'error');
            return null;
        }
        
        $system_prompt = "You are a lead processing assistant. Analyze the lead data and provide insights. Always respond with valid JSON only. Do not include any explanations or text outside of JSON. Response format must be: {\"lead_quality\": \"\", \"estimated_value\": \"\", \"urgency\": \"\", \"recommended_action\": \"\", \"key_notes\": \"\"}";
        
        $user_prompt = "Analyze this lead data: " . json_encode($data);
        
        switch ($provider) {
            case 'openai':
                return $this->call_openai($api_key, $system_prompt, $user_prompt);
            case 'deepseek':
                return $this->call_deepseek($api_key, $system_prompt, $user_prompt);
            case 'custom':
                return $this->call_custom_api($api_key, $system_prompt, $user_prompt);
            default:
                return null;
        }
    }
    
    private function call_openai($api_key, $system_prompt, $user_prompt) {
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => $system_prompt],
                    ['role' => 'user', 'content' => $user_prompt]
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.1
            ]),
            'timeout' => 30
        ]);
        
        return $this->handle_response($response);
    }
    
    private function call_deepseek($api_key, $system_prompt, $user_prompt) {
        $custom_url = get_option('bestway_forms_ai_custom_url');
        $api_url = $custom_url ?: 'https://api.deepseek.com/chat/completions';
        
        $response = wp_remote_post($api_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'model' => 'deepseek-chat',
                'messages' => [
                    ['role' => 'system', 'content' => $system_prompt],
                    ['role' => 'user', 'content' => $user_prompt]
                ],
                'stream' => false,
                'temperature' => 0.1
            ]),
            'timeout' => 30
        ]);
        
        return $this->handle_response($response);
    }
    
    private function call_custom_api($api_key, $system_prompt, $user_prompt) {
        $custom_url = get_option('bestway_forms_ai_custom_url');
        if (!$custom_url) {
            BestwayForms::log('Custom API URL not set', 'error');
            return null;
        }
        
        $headers = [
            'Content-Type' => 'application/json'
        ];
        
        if ($api_key) {
            $auth_type = get_option('bestway_forms_ai_auth_type', 'bearer');
            if ($auth_type === 'bearer') {
                $headers['Authorization'] = 'Bearer ' . $api_key;
            } elseif ($auth_type === 'api_key') {
                $headers['X-API-Key'] = $api_key;
            }
        }
        
        $request_body = [
            'model' => get_option('bestway_forms_ai_custom_model', 'gpt-3.5-turbo'),
            'messages' => [
                ['role' => 'system', 'content' => $system_prompt],
                ['role' => 'user', 'content' => $user_prompt]
            ],
            'temperature' => 0.1
        ];
        
        $custom_params = get_option('bestway_forms_ai_custom_params');
        if ($custom_params) {
            $params = json_decode($custom_params, true);
            if ($params) {
                $request_body = array_merge($request_body, $params);
            }
        }
        
        $response = wp_remote_post($custom_url, [
            'headers' => $headers,
            'body' => json_encode($request_body),
            'timeout' => 30
        ]);
        
        return $this->handle_response($response);
    }
    
    private function handle_response($response) {
        if (is_wp_error($response)) {
            BestwayForms::log('AI API error: ' . $response->get_error_message(), 'error');
            return null;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['choices'][0]['message']['content'])) {
            $content = $data['choices'][0]['message']['content'];
            $json_data = json_decode($content, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                return $json_data;
            } else {
                BestwayForms::log('AI response JSON decode error: ' . json_last_error_msg(), 'error');
                return ['raw_content' => $content, 'json_error' => json_last_error_msg()];
            }
        }
        
        if (isset($data['choices'][0]['message']['content'])) {
            $content = $data['choices'][0]['message']['content'];
            $json_data = json_decode($content, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                return $json_data;
            }
        }
        
        BestwayForms::log('AI response format invalid: ' . $body, 'error');
        return $data ?: ['raw_response' => $body];
    }
}
