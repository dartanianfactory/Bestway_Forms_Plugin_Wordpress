<?php
if (!defined('ABSPATH')) exit;

class BestwayForms_Tab_AI_Render {
    
    public static function render() {
        if (!get_option('bestway_forms_ai_enabled')) {
            echo '<div class="notice notice-warning"><p>AI Менеджер отключен. Включите его в общих настройках.</p></div>';
            return;
        }

        $ai_providers = [
            'openai' => 'OpenAI ChatGPT',
            'deepseek' => 'DeepSeek',
            'custom' => 'Кастомный API'
        ];
        
        $data_sources = [
            'forms' => 'Данные форм',
            'woocommerce' => 'Заказы WooCommerce',
            'all' => 'Все данные',
            'no_ai' => 'Без AI обработки'
        ];

        $auth_types = [
            'bearer' => 'Bearer Token',
            'api_key' => 'API Key Header'
        ];
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('bestway_forms_ai'); ?>
            
            <div class="card">
                <h2>Настройки AI Менеджера</h2>
                <p>Встроенный системный промт для анализа лидов в JSON формате. AI автоматически обрабатывает данные форм и заказов.</p>
            </div>
            
            <div class="card">
                <h3>AI Провайдер</h3>
                <table class="form-table">
                    <tr>
                        <th>AI Провайдер</th>
                        <td>
                            <select name="bestway_forms_ai_provider" id="ai_provider">
                                <?php foreach ($ai_providers as $value => $label): ?>
                                    <option value="<?php echo esc_attr($value); ?>" <?php selected(get_option('bestway_forms_ai_provider'), $value); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <strong>OpenAI ChatGPT</strong> - стандартный API от OpenAI<br>
                                <strong>DeepSeek</strong> - современная нейросеть с высоким качеством ответов<br>
                                <strong>Кастомный API</strong> - для любых других нейросетей
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th>API Ключ</th>
                        <td>
                            <input type="password" name="bestway_forms_ai_api_key" value="<?php echo esc_attr(get_option('bestway_forms_ai_api_key')); ?>" class="regular-text">
                            <p class="description">
                                Для OpenAI: ключ из <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a><br>
                                Для DeepSeek: ключ из <a href="https://platform.deepseek.com/api_keys" target="_blank">DeepSeek Platform</a>
                            </p>
                        </td>
                    </tr>
                    
                    <tr id="custom_url_row" style="display: none;">
                        <th>URL API</th>
                        <td>
                            <input type="url" name="bestway_forms_ai_custom_url" value="<?php echo esc_attr(get_option('bestway_forms_ai_custom_url', 'https://api.deepseek.com/chat/completions')); ?>" class="regular-text" placeholder="https://api.deepseek.com/chat/completions">
                            <p class="description">URL endpoint для API запросов</p>
                        </td>
                    </tr>
                    
                    <tr id="custom_model_row" style="display: none;">
                        <th>Модель</th>
                        <td>
                            <input type="text" name="bestway_forms_ai_custom_model" value="<?php echo esc_attr(get_option('bestway_forms_ai_custom_model', 'deepseek-chat')); ?>" class="regular-text" placeholder="deepseek-chat">
                            <p class="description">Название модели для кастомного API</p>
                        </td>
                    </tr>
                    
                    <tr id="auth_type_row" style="display: none;">
                        <th>Тип авторизации</th>
                        <td>
                            <select name="bestway_forms_ai_auth_type">
                                <?php foreach ($auth_types as $value => $label): ?>
                                    <option value="<?php echo esc_attr($value); ?>" <?php selected(get_option('bestway_forms_ai_auth_type', 'bearer'), $value); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    
                    <tr id="custom_params_row" style="display: none;">
                        <th>Дополнительные параметры</th>
                        <td>
                            <textarea name="bestway_forms_ai_custom_params" class="large-text" rows="3" placeholder='{"temperature": 0.1, "max_tokens": 1000}'><?php echo esc_textarea(get_option('bestway_forms_ai_custom_params')); ?></textarea>
                            <p class="description">JSON с дополнительными параметрами для API (temperature, max_tokens и т.д.)</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="card">
                <h3>Обработка данных</h3>
                <table class="form-table">
                    <tr>
                        <th>Источник данных</th>
                        <td>
                            <select name="bestway_forms_ai_data_source" id="data_source">
                                <?php foreach ($data_sources as $value => $label): ?>
                                    <option value="<?php echo esc_attr($value); ?>" <?php selected(get_option('bestway_forms_ai_data_source'), $value); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">Выберите какие данные обрабатывать AI</p>
                        </td>
                    </tr>
                    
                    <tr id="prompt_section">
                        <th>AI Промты</th>
                        <td>
                            <div class="prompt-field">
                                <label><strong>Системный промт</strong> (обязательный)</label>
                                <textarea name="bestway_forms_ai_system_prompt" class="large-text" rows="4" placeholder="You are a lead processing assistant. Analyze the lead data and provide insights. Always respond with valid JSON only. Do not include any explanations or text outside of JSON. Response format must be: {&quot;lead_quality&quot;: &quot;&quot;, &quot;estimated_value&quot;: &quot;&quot;, &quot;urgency&quot;: &quot;&quot;, &quot;recommended_action&quot;: &quot;&quot;, &quot;key_notes&quot;: &quot;&quot;}"><?php echo esc_textarea(get_option('bestway_forms_ai_system_prompt', "You are a lead processing assistant. Analyze the lead data and provide insights. Always respond with valid JSON only. Do not include any explanations or text outside of JSON. Response format must be: {\"lead_quality\": \"\", \"estimated_value\": \"\", \"urgency\": \"\", \"recommended_action\": \"\", \"key_notes\": \"\"}")); ?></textarea>
                                <p class="description">Определяет роль AI и формат ответа (всегда JSON)</p>
                            </div>
                            
                            <div class="prompt-field">
                                <label><strong>Промт анализа</strong> (опциональный)</label>
                                <textarea name="bestway_forms_ai_analysis_prompt" class="large-text" rows="3" placeholder="Analyze this lead data and provide quality assessment:"><?php echo esc_textarea(get_option('bestway_forms_ai_analysis_prompt')); ?></textarea>
                                <p class="description">Дополнительные инструкции для анализа данных</p>
                            </div>
                            
                            <div class="prompt-field">
                                <label><strong>Промт ответа</strong> (опциональный)</label>
                                <textarea name="bestway_forms_ai_response_prompt" class="large-text" rows="3" placeholder="Generate personalized response based on analysis:"><?php echo esc_textarea(get_option('bestway_forms_ai_response_prompt')); ?></textarea>
                                <p class="description">Инструкции для генерации ответов клиентам</p>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="card">
                <h3>Тестирование AI</h3>
                <table class="form-table">
                    <tr>
                        <th>Быстрая проверка</th>
                        <td>
                            <button type="button" id="test_ai_connection" class="button">Проверить подключение к AI</button>
                            <span id="test_ai_result" style="margin-left: 10px;"></span>
                            <p class="description">Проверяет подключение к выбранному AI провайдеру</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <?php submit_button('Сохранить AI настройки'); ?>
        </form>

        <script>
        jQuery(document).ready(function($) {
            function toggleAIFields() {
                var provider = $('#ai_provider').val();
                var dataSource = $('#data_source').val();
                
                // Показываем/скрываем поля для кастомного API
                var isCustom = provider === 'custom';
                $('#custom_url_row').toggle(isCustom);
                $('#custom_model_row').toggle(isCustom);
                $('#auth_type_row').toggle(isCustom);
                $('#custom_params_row').toggle(isCustom);
                
                // Показываем/скрываем промты в зависимости от источника данных
                $('#prompt_section').toggle(dataSource !== 'no_ai');
                
                // Для DeepSeek показываем подсказки
                if (provider === 'deepseek') {
                    $('input[name="bestway_forms_ai_custom_url"]').val('https://api.deepseek.com/chat/completions');
                    $('input[name="bestway_forms_ai_custom_model"]').val('deepseek-chat');
                }
            }
            
            $('#ai_provider, #data_source').change(toggleAIFields);
            toggleAIFields();

            // Тестирование AI подключения
            $('#test_ai_connection').on('click', function() {
                var $button = $(this);
                var $result = $('#test_ai_result');
                
                $button.prop('disabled', true).text('Проверяем...');
                $result.html('');
                
                $.post(ajaxurl, {
                    action: 'test_ai_connection',
                    nonce: '<?php echo wp_create_nonce('bestway_forms_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        $result.html('<span style="color: green;">✓ ' + response.data + '</span>');
                    } else {
                        $result.html('<span style="color: red;">✗ ' + response.data + '</span>');
                    }
                    $button.prop('disabled', false).text('Проверить подключение к AI');
                }).fail(function() {
                    $result.html('<span style="color: red;">✗ Ошибка запроса</span>');
                    $button.prop('disabled', false).text('Проверить подключение к AI');
                });
            });
        });
        </script>

        <style>
        .prompt-field {
            margin-bottom: 15px;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .prompt-field label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        </style>
        <?php
    }
}
