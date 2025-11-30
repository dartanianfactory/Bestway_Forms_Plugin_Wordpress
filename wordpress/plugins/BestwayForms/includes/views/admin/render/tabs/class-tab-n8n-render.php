<?php
if (!defined('ABSPATH')) exit;

class BestwayForms_Tab_N8N_Render {
    
    public static function render() {
        if (!get_option('bestway_forms_n8n_enabled')) {
            echo '<div class="notice notice-warning"><p>n8n интеграция отключена. Включите ее в общих настройках.</p></div>';
            return;
        }
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('bestway_forms_n8n'); ?>
            
            <div class="card">
                <h2>n8n Интеграция</h2>

                <p>n8n должен принимать post и отдавать JSON (!)</p>
            </div>
            
            <div class="card">
                <h3>Настройки n8n</h3>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="n8n_webhook_url">Webhook URL</label>
                        </th>
                        <td>
                            <input type="url" 
                                   id="n8n_webhook_url" 
                                   name="bestway_forms_n8n_webhook_url" 
                                   value="<?php echo esc_attr(get_option('bestway_forms_n8n_webhook_url')); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="n8n_api_key">API Ключ</label>
                        </th>
                        <td>
                            <input type="password" 
                                   id="n8n_api_key" 
                                   name="bestway_forms_n8n_api_key" 
                                   value="<?php echo esc_attr(get_option('bestway_forms_n8n_api_key')); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
                </table>
            </div>
            
            <?php submit_button('Сохранить настройки n8n'); ?>
        </form>
        <?php
    }
}
