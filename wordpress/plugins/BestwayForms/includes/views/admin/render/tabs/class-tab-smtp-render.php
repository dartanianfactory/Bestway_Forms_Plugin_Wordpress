<?php
if (!defined('ABSPATH')) exit;

class BestwayForms_Tab_SMTP_Render {
    
    public static function render() {
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('bestway_forms_smtp'); ?>
            
            <div class="card">
                <h2>Настройки SMTP и Email уведомлений</h2>
                <p>Настройте отправку писем через SMTP сервер и укажите, на какие email отправлять уведомления о новых лидах.</p>
            </div>
            
            <div class="card">
                <h3>Параметры SMTP сервера</h3>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="smtp_host">SMTP Хост</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="smtp_host" 
                                   name="bestway_forms_smtp_host" 
                                   value="<?php echo esc_attr(get_option('bestway_forms_smtp_host')); ?>" 
                                   class="regular-text">
                            <p class="description">Например: smtp.gmail.com, smtp.yandex.ru</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="smtp_port">Порт</label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="smtp_port" 
                                   name="bestway_forms_smtp_port" 
                                   value="<?php echo esc_attr(get_option('bestway_forms_smtp_port', '587')); ?>" 
                                   class="small-text">
                            <p class="description">Обычно 587 для TLS или 465 для SSL</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="smtp_encryption">Шифрование</label>
                        </th>
                        <td>
                            <select id="smtp_encryption" name="bestway_forms_smtp_encryption">
                                <option value="">Без шифрования</option>
                                <option value="ssl" <?php selected(get_option('bestway_forms_smtp_encryption'), 'ssl'); ?>>SSL</option>
                                <option value="tls" <?php selected(get_option('bestway_forms_smtp_encryption'), 'tls'); ?>>TLS</option>
                            </select>
                            <p class="description">Выберите тип шифрования соединения</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="smtp_username">Имя пользователя</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="smtp_username" 
                                   name="bestway_forms_smtp_username" 
                                   value="<?php echo esc_attr(get_option('bestway_forms_smtp_username')); ?>" 
                                   class="regular-text">
                            <p class="description">Обычно это полный email адрес</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="smtp_password">Пароль</label>
                        </th>
                        <td>
                            <input type="password" 
                                   id="smtp_password" 
                                   name="bestway_forms_smtp_password" 
                                   value="<?php echo esc_attr(get_option('bestway_forms_smtp_password')); ?>" 
                                   class="regular-text">
                            <p class="description">Пароль для SMTP аутентификации</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="smtp_from_email">Email отправителя</label>
                        </th>
                        <td>
                            <input type="email" 
                                   id="smtp_from_email" 
                                   name="bestway_forms_smtp_from_email" 
                                   value="<?php echo esc_attr(get_option('bestway_forms_smtp_from_email')); ?>" 
                                   class="regular-text">
                            <p class="description">Email, который будет указан как отправитель писем</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="smtp_from_name">Имя отправителя</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="smtp_from_name" 
                                   name="bestway_forms_smtp_from_name" 
                                   value="<?php echo esc_attr(get_option('bestway_forms_smtp_from_name', get_bloginfo('name'))); ?>" 
                                   class="regular-text">
                            <p class="description">Имя, которое будет отображаться как отправитель</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="card">
                <h3>Настройки уведомлений о лидах</h3>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="smtp_notifications_enabled">Включить уведомления</label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       id="smtp_notifications_enabled" 
                                       name="bestway_forms_smtp_notifications_enabled" 
                                       value="1" 
                                       <?php checked(get_option('bestway_forms_smtp_notifications_enabled'), '1'); ?>>
                                Отправлять email уведомления о новых лидах
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="smtp_lead_types">Типы лидов для уведомлений</label>
                        </th>
                        <td>
                            <select id="smtp_lead_types" name="bestway_forms_smtp_lead_types">
                                <option value="all" <?php selected(get_option('bestway_forms_smtp_lead_types'), 'all'); ?>>Все лиды (формы + WooCommerce)</option>
                                <option value="forms_only" <?php selected(get_option('bestway_forms_smtp_lead_types'), 'forms_only'); ?>>Только формы</option>
                                <option value="woocommerce_only" <?php selected(get_option('bestway_forms_smtp_lead_types'), 'woocommerce_only'); ?>>Только WooCommerce</option>
                            </select>
                            <p class="description">Выберите, о каких лидах отправлять уведомления</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="smtp_recipient_emails">Email получателей</label>
                        </th>
                        <td>
                            <textarea 
                                id="smtp_recipient_emails" 
                                name="bestway_forms_smtp_recipient_emails" 
                                class="large-text" 
                                rows="3" 
                                placeholder="email1@example.com, email2@example.com"><?php echo esc_textarea(get_option('bestway_forms_smtp_recipient_emails')); ?></textarea>
                            <p class="description">Укажите email адреса через запятую для получения уведомлений о новых лидах</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="smtp_email_subject">Тема письма</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="smtp_email_subject" 
                                   name="bestway_forms_smtp_email_subject" 
                                   value="<?php echo esc_attr(get_option('bestway_forms_smtp_email_subject', 'Новый лид с сайта')); ?>" 
                                   class="regular-text">
                            <p class="description">Тема email уведомления о новом лиде</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="card">
                <h3>Тестирование настроек</h3>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label>Проверка подключения</label>
                        </th>
                        <td>
                            <button type="button" id="test-smtp-connection" class="button button-secondary">
                                Проверить SMTP подключение
                            </button>
                            <span id="test-smtp-result" style="margin-left: 10px;"></span>
                            <p class="description">Отправит тестовое письмо на первый указанный email получателя</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <?php submit_button('Сохранить SMTP настройки'); ?>
        </form>

        <script>
        jQuery(document).ready(function($) {
            $('#test-smtp-connection').on('click', function() {
                var $button = $(this);
                var $result = $('#test-smtp-result');
                
                $button.prop('disabled', true).text('Проверка...');
                $result.html('<span class="spinner is-active" style="float:none;"></span>');
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'bestway_test_smtp',
                        nonce: '<?php echo wp_create_nonce('bestway_forms_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $result.html('<span style="color: green;">✓ ' + response.data + '</span>');
                        } else {
                            $result.html('<span style="color: red;">✗ ' + response.data + '</span>');
                        }
                    },
                    error: function() {
                        $result.html('<span style="color: red;">✗ Ошибка подключения</span>');
                    },
                    complete: function() {
                        $button.prop('disabled', false).text('Проверить SMTP подключение');
                    }
                });
            });
        });
        </script>
        <?php
    }
}
