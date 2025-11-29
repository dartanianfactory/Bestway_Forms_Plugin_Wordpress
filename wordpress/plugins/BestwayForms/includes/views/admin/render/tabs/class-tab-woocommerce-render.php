<?php
if (!defined('ABSPATH')) exit;

class BestwayForms_Tab_WooCommerce_Render {
    
    public static function render() {
        if (!get_option('bestway_forms_wc_enabled')) {
            echo '<div class="notice notice-warning"><p>Интеграция WooCommerce отключена. Включите ее в общих настройках.</p></div>';
            return;
        }

        if (!class_exists('WooCommerce')) {
            echo '<div class="notice notice-warning"><p>WooCommerce не установлен или не активирован.</p></div>';
            return;
        }

        $order_statuses = wc_get_order_statuses();
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('bestway_forms_wc'); ?>
            
            <div class="card">
                <h2>Интеграция WooCommerce</h2>

                <p>Простая интеграция - мощный инструмент</p>
                <p>Настройка работы с захватом заказов</p>
            </div>
            
            <div class="card">
                <h3>Настройки сбора заказов</h3>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="wc_capture_all">Собирать все заказы</label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       id="wc_capture_all" 
                                       name="bestway_forms_wc_capture_all" 
                                       value="1" 
                                       <?php checked(get_option('bestway_forms_wc_capture_all'), '1'); ?>>
                                Собирать все заказы WooCommerce
                            </label>
                        </td>
                    </tr>
                    
                    <tr id="wc_statuses_row" style="display: none;">
                        <th scope="row">
                            <label>Собирать определенные статусы</label>
                        </th>
                        <td>
                            <?php foreach ($order_statuses as $status_key => $status_label): ?>
                                <label style="display: block; margin-bottom: 5px;">
                                    <input type="checkbox" 
                                           name="bestway_forms_wc_statuses[]" 
                                           value="<?php echo esc_attr($status_key); ?>"
                                           <?php 
                                           $selected_statuses = get_option('bestway_forms_wc_statuses', []);
                                           if (!is_array($selected_statuses)) $selected_statuses = [];
                                           checked(in_array($status_key, $selected_statuses)); ?>>
                                    <?php echo esc_html($status_label); ?>
                                </label>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                </table>
            </div>
            
            <?php submit_button('Сохранить настройки WooCommerce'); ?>
        </form>

        <script>
        jQuery(document).ready(function($) {
            function toggleStatusSelection() {
                const captureAll = $('#wc_capture_all').is(':checked');
                $('#wc_statuses_row').toggle(!captureAll);
            }
            
            $('#wc_capture_all').change(toggleStatusSelection);
            toggleStatusSelection();
        });
        </script>
        <?php
    }
}