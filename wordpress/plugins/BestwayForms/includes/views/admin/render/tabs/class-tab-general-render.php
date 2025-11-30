<?php
if (!defined('ABSPATH')) exit;

class BestwayForms_Tab_General_Render {
    
    public static function render() {
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('bestway_forms_general'); ?>
            
            <div class="card">
                <h2>Добро пожаловать в BestwayForms</h2>

                <p>Я вкладываю много души, сил и времени в проекты, но они почти не приносят дохода.</p>
                <p>Прошу поддержать меня, если вы используете плагин и не купили его по какой-то причине. </p>
                <p>Любая сумма по номеру карты ниже: </p>
                
                <h3>2203 8303 1875 8787</h3>

                <p>На всякий случай лучше свериться с картой в <a href="https://github.com/dartanianfactory/Bestway_Forms_Plugin_Wordpress" target="_blank">github</a></p>
                <p>Приятного использования!</p>
            </div>
            
            <div class="card">
                <h3>Настройки BestwayForms</h3>
                
                <table class="form-table">
                    <tr>
                        <th>n8n Интеграция</th>
                        <td>
                            <label>
                                <input type="checkbox" name="bestway_forms_n8n_enabled" value="1" <?php checked(get_option('bestway_forms_n8n_enabled'), '1'); ?>>
                                Включить n8n интеграцию
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th>AI Менеджер</th>
                        <td>
                            <label>
                                <input type="checkbox" name="bestway_forms_ai_enabled" value="1" <?php checked(get_option('bestway_forms_ai_enabled'), '1'); ?>>
                                Включить AI Менеджер
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th>WooCommerce</th>
                        <td>
                            <label>
                                <input type="checkbox" name="bestway_forms_wc_enabled" value="1" <?php checked(get_option('bestway_forms_wc_enabled'), '1'); ?>>
                                Включить интеграцию с WooCommerce
                            </label>
                        </td>
                    </tr>
                </table>
            </div>
            
            <?php submit_button('Сохранить общие настройки'); ?>
        </form>
        <?php
    }
}
