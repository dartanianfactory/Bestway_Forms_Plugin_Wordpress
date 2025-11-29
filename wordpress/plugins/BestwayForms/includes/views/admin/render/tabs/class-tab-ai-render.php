<?php
if (!defined('ABSPATH')) exit;

class BestwayForms_Tab_AI_Render {
    
    public static function render() {
        if (!get_option('bestway_forms_ai_enabled')) {
            echo '<div class="notice notice-warning"><p>AI Менеджер отключен. Включите его в общих настройках.</p></div>';
            return;
        }

        $ai_providers = [
            'openai' => 'OpenAI GPT',
            'claude' => 'Anthropic Claude', 
            'gemini' => 'Google Gemini',
            'custom' => 'Кастомный API'
        ];
        
        $data_sources = [
            'forms' => 'Данные форм',
            'woocommerce' => 'Заказы WooCommerce',
            'all' => 'Все данные',
            'no_ai' => 'Без AI обработки'
        ];
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('bestway_forms_ai'); ?>
            
            <div class="card">
                <h2>Настройки AI Менеджера</h2>

                <p>В провайдер уже встроен системный промт для отдачи ответов в json'его</p>
                <p>Если включено обрабатывает форму в соответствии с промтами.</p>
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
                        </td>
                    </tr>
                    <tr>
                        <th>API Ключ</th>
                        <td>
                            <input type="password" name="bestway_forms_ai_api_key" value="<?php echo esc_attr(get_option('bestway_forms_ai_api_key')); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr id="custom_url_row" style="display: none;">
                        <th>Кастомный URL API</th>
                        <td>
                            <input type="url" name="bestway_forms_ai_custom_url" value="<?php echo esc_attr(get_option('bestway_forms_ai_custom_url')); ?>" class="regular-text">
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
                        </td>
                    </tr>
                    
                    <tr id="prompt_section">
                        <th>AI Промты</th>
                        <td>
                            <div class="prompt-field">
                                <label>Системный промт</label>
                                <textarea name="bestway_forms_ai_system_prompt" class="large-text" rows="3" placeholder="Вы помощник по квалификации лидов..."><?php echo esc_textarea(get_option('bestway_forms_ai_system_prompt')); ?></textarea>
                            </div>
                            
                            <div class="prompt-field">
                                <label>Промт анализа</label>
                                <textarea name="bestway_forms_ai_analysis_prompt" class="large-text" rows="3" placeholder="Проанализируйте этого лида и предоставьте инсайты..."><?php echo esc_textarea(get_option('bestway_forms_ai_analysis_prompt')); ?></textarea>
                            </div>
                            
                            <div class="prompt-field">
                                <label>Промт ответа</label>
                                <textarea name="bestway_forms_ai_response_prompt" class="large-text" rows="3" placeholder="Сгенерируйте персонализированный ответ..."><?php echo esc_textarea(get_option('bestway_forms_ai_response_prompt')); ?></textarea>
                            </div>
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
                
                $('#custom_url_row').toggle(provider === 'custom');
                $('#prompt_section').toggle(dataSource !== 'no_ai');
            }
            
            $('#ai_provider, #data_source').change(toggleAIFields);
            toggleAIFields();
        });
        </script>
        <?php
    }
}
