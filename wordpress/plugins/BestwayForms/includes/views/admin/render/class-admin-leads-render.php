<?php
if (!defined('ABSPATH')) exit;

class BestwayForms_Admin_Leads_Render {
    
    public static function render($leads, $current_page = 1, $total_pages = 1, $total_items = 0) {
        $forms_model = new BestwayForms_Model_Forms();
        $per_page = 50;
        
        $export_nonce = wp_create_nonce('export_leads');
        ?>
        <div class="wrap">
            <h1>Лиды (<?php echo $total_items; ?>)</h1>
            
            <div class="tablenav top">
                <div class="alignleft actions">
                    <div class="export-buttons" style="display: flex; gap: 10px; align-items: center;">
                        <span><strong>Экспорт:</strong></span>
                        <a href="<?php echo admin_url('admin-ajax.php?action=export_leads_csv&nonce=' . $export_nonce); ?>" 
                           class="button button-secondary" id="export-csv">
                            CSV
                        </a>
                        <a href="<?php echo admin_url('admin-ajax.php?action=export_leads_excel&nonce=' . $export_nonce); ?>" 
                           class="button button-secondary" id="export-excel">
                            Excel
                        </a>
                        <a href="<?php echo admin_url('admin-ajax.php?action=export_leads_json&nonce=' . $export_nonce); ?>" 
                           class="button button-secondary" id="export-json">
                            JSON
                        </a>
                    </div>
                </div>
                
                <div class="tablenav-pages">
                    <span class="displaying-num"><?php echo $total_items; ?> записей</span>
                    <?php if ($total_pages > 1): ?>
                        <span class="pagination-links">
                            <?php
                            echo paginate_links(array(
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'prev_text' => '&laquo;',
                                'next_text' => '&raquo;',
                                'total' => $total_pages,
                                'current' => $current_page
                            ));
                            ?>
                        </span>
                    <?php endif; ?>
                </div>
                <br class="clear">
            </div>
            
            <?php if ($total_items > 1000): ?>
                <div class="notice notice-info">
                    <p>Для удобства работы с большим количеством лидов используйте экспорт. Всего лидов: <?php echo $total_items; ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (empty($leads)): ?>
                <div class="notice notice-warning">
                    <p>Лидов пока нет. Данные появятся после отправки форм или создания заказов WooCommerce.</p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Тип</th>
                            <th>Источник</th>
                            <th>Данные</th>
                            <th>n8n Ответ</th>
                            <th>AI Анализ</th>
                            <th>Статус</th>
                            <th>Дата</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leads as $lead): 
                            $is_wc_lead = (strpos($lead->id, 'wc_') === 0) || isset($lead->order_id);
                            $form = null;
                            $form_data = [];
                            $form_fields = [];
                            
                            if ($is_wc_lead) {
                                // WooCommerce лид
                                $customer_data = json_decode($lead->form_data ?? $lead->customer_data, true);
                                $order_data = isset($lead->order_data) ? json_decode($lead->order_data, true) : [];
                                $form_data = array_merge($customer_data ?? [], $order_data ?? []);
                            } else {
                                // Обычный лид формы
                                $form = $forms_model->get_form($lead->form_id);
                                $form_data = json_decode($lead->form_data, true);
                                
                                if ($form) {
                                    $form_settings = json_decode($form->settings, true);
                                    $form_fields = $form_settings['fields'] ?? [];
                                }
                            }
                        ?>
                            <tr>
                                <td><?php echo esc_html($lead->id); ?></td>
                                <td>
                                    <?php if ($is_wc_lead): ?>
                                        <span class="dashicons dashicons-cart" title="WooCommerce"></span> WC
                                    <?php else: ?>
                                        <span class="dashicons dashicons-email-alt" title="Форма"></span> Форма
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($is_wc_lead): ?>
                                        WooCommerce
                                        <?php if (isset($lead->order_id)): ?>
                                            <br><small>Заказ #<?php echo esc_html($lead->order_id); ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if ($form): ?>
                                            <?php echo esc_html($form->name); ?> (ID: <?php echo esc_html($form->id); ?>)
                                        <?php else: ?>
                                            Форма #<?php echo esc_html($lead->form_id); ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($is_wc_lead) {
                                        // Данные WooCommerce
                                        $display_data = [];
                                        if (isset($form_data['first_name']) || isset($form_data['last_name'])) {
                                            $name = trim(($form_data['first_name'] ?? '') . ' ' . ($form_data['last_name'] ?? ''));
                                            if (!empty($name)) {
                                                $display_data[] = '<strong>Имя:</strong> ' . esc_html($name);
                                            }
                                        }
                                        if (isset($form_data['email'])) {
                                            $display_data[] = '<strong>Email:</strong> ' . esc_html($form_data['email']);
                                        }
                                        if (isset($form_data['phone'])) {
                                            $display_data[] = '<strong>Телефон:</strong> ' . esc_html($form_data['phone']);
                                        }
                                        if (isset($form_data['order_total'])) {
                                            $display_data[] = '<strong>Сумма:</strong> ' . esc_html($form_data['order_total']);
                                        }
                                        echo implode('<br>', $display_data);
                                    } else {
                                        // Данные обычной формы
                                        if (!empty($form_fields)) {
                                            $display_data = [];
                                            foreach ($form_fields as $field_name => $field_config) {
                                                if (isset($form_data[$field_name]) && !empty($form_data[$field_name])) {
                                                    $label = $field_config['label'] ?? ucfirst(str_replace('_', ' ', $field_name));
                                                    $display_data[] = '<strong>' . esc_html($label) . ':</strong> ' . esc_html($form_data[$field_name]);
                                                }
                                            }
                                            echo implode('<br>', $display_data);
                                        } else {
                                            echo esc_html($form_data['name'] ?? 'N/A') . ' - ' . esc_html($form_data['email'] ?? 'N/A');
                                        }
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($lead->n8n_response): ?>
                                        <button type="button" class="button button-small view-response" data-response='<?php echo esc_attr($lead->n8n_response); ?>' data-type="n8n">
                                            Просмотр
                                        </button>
                                    <?php else: ?>
                                        <span class="dashicons dashicons-no" title="Нет данных"></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($lead->ai_processed_data): ?>
                                        <button type="button" class="button button-small view-response" data-response='<?php echo esc_attr($lead->ai_processed_data); ?>' data-type="ai">
                                            Просмотр
                                        </button>
                                    <?php else: ?>
                                        <span class="dashicons dashicons-no" title="Нет данных"></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo esc_attr($lead->status); ?>">
                                        <?php 
                                        $status_labels = [
                                            'new' => 'Новый',
                                            'processed' => 'Обработан',
                                            'qualified' => 'Квалифицирован',
                                            'rejected' => 'Отклонен'
                                        ];
                                        echo esc_html($status_labels[$lead->status] ?? $lead->status);
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html(date('d.m.Y H:i', strtotime($lead->created_at))); ?></td>
                                <td>
                                    <button type="button" class="button button-small view-full-details" 
                                            data-form-data='<?php echo esc_attr(wp_json_encode($form_data)); ?>'
                                            data-form-fields='<?php echo esc_attr(wp_json_encode($form_fields)); ?>'
                                            data-form-name="<?php echo $is_wc_lead ? 'WooCommerce' : ($form ? esc_attr($form->name) : ''); ?>"
                                            data-is-wc="<?php echo $is_wc_lead ? '1' : '0'; ?>">
                                        Полные данные
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if ($total_pages > 1): ?>
                    <div class="tablenav bottom">
                        <div class="tablenav-pages">
                            <span class="displaying-num"><?php echo $total_items; ?> записей</span>
                            <span class="pagination-links">
                                <?php
                                echo paginate_links(array(
                                    'base' => add_query_arg('paged', '%#%'),
                                    'format' => '',
                                    'prev_text' => '&laquo;',
                                    'next_text' => '&raquo;',
                                    'total' => $total_pages,
                                    'current' => $current_page
                                ));
                                ?>
                            </span>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <div id="response-modal" style="display: none;">
            <div class="modal-content">
                <h3 id="modal-title">Данные ответа</h3>
                <div id="modal-content"></div>
                <div class="modal-actions">
                    <button type="button" class="button" id="close-modal">Закрыть</button>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('.export-buttons a').on('click', function(e) {
                e.preventDefault();
                var url = $(this).attr('href');
                
                var iframe = document.createElement('iframe');
                iframe.style.display = 'none';
                iframe.src = url;
                document.body.appendChild(iframe);
            });
            
            $('.view-response').on('click', function() {
                var response = $(this).data('response');
                var type = $(this).data('type');
                var title = type === 'n8n' ? 'n8n Ответ' : 'AI Анализ';
                
                try {
                    var parsedResponse = JSON.parse(response);
                    var formattedResponse = JSON.stringify(parsedResponse, null, 2);
                } catch (e) {
                    var formattedResponse = response;
                }
                
                $('#modal-title').text(title);
                $('#modal-content').html('<pre>' + formattedResponse + '</pre>');
                $('#response-modal').show();
            });
            
            $('.view-full-details').on('click', function() {
                var formData = $(this).data('form-data');
                var formFields = $(this).data('form-fields');
                var formName = $(this).data('form-name');
                var isWc = $(this).data('is-wc');
                
                try {
                    var parsedData = typeof formData === 'string' ? JSON.parse(formData) : formData;
                    var content = '<h4>Данные: ' + formName + '</h4><table class="widefat">';
                    
                    if (isWc == '1') {
                        // WooCommerce данные
                        $.each(parsedData, function(key, value) {
                            if (key === 'items' && Array.isArray(value)) {
                                content += '<tr><td colspan="2"><strong>Товары:</strong></td></tr>';
                                $.each(value, function(index, item) {
                                    content += '<tr><td style="padding-left: 20px;" colspan="2">';
                                    content += '<strong>Товар ' + (index + 1) + ':</strong><br>';
                                    content += 'Название: ' + (item.product_name || item.name || 'N/A') + '<br>';
                                    content += 'Количество: ' + (item.quantity || '0') + '<br>';
                                    content += 'Цена: ' + (item.total || item.price || '0');
                                    content += '</td></tr>';
                                });
                            } else if (value && typeof value === 'object' && !Array.isArray(value)) {
                                content += '<tr><td colspan="2"><strong>' + key + ':</strong></td></tr>';
                                $.each(value, function(subKey, subValue) {
                                    content += '<tr><td style="padding-left: 20px;">' + subKey + '</td><td>' + subValue + '</td></tr>';
                                });
                            } else {
                                content += '<tr><td><strong>' + key + '</strong></td><td>' + value + '</td></tr>';
                            }
                        });
                    } else if (formFields && Object.keys(formFields).length > 0) {
                        // Данные формы с полями
                        $.each(formFields, function(fieldName, fieldConfig) {
                            var value = parsedData[fieldName] || 'Не заполнено';
                            var label = fieldConfig.label || fieldName;
                            content += '<tr><td><strong>' + label + '</strong></td><td>' + value + '</td></tr>';
                        });
                    } else {
                        // Простые данные формы
                        $.each(parsedData, function(key, value) {
                            content += '<tr><td><strong>' + key + '</strong></td><td>' + value + '</td></tr>';
                        });
                    }
                    
                    content += '</table>';
                    $('#modal-title').text('Полные данные лида');
                    $('#modal-content').html(content);
                } catch (e) {
                    $('#modal-content').html('<p>Ошибка при разборе данных: ' + e.message + '</p>');
                }
                
                $('#response-modal').show();
            });
            
            $('#close-modal').on('click', function() {
                $('#response-modal').hide();
            });
            
            $('#response-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).hide();
                }
            });
        });
        </script>
        
        <style>
        #response-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        
        #response-modal .modal-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            max-width: 800px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        #response-modal pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
        
        #response-modal table {
            margin-top: 15px;
            width: 100%;
        }
        
        #response-modal table td {
            padding: 8px 12px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }
        
        .modal-actions {
            margin-top: 20px;
            text-align: right;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-new {
            background: #e7f3ff;
            color: #0073aa;
        }
        
        .status-processed {
            background: #f0f7f0;
            color: #28a745;
        }
        
        .status-qualified {
            background: #fff8e5;
            color: #ffc107;
        }
        
        .status-rejected {
            background: #fdf2f2;
            color: #dc3545;
        }
        
        .export-buttons {
            margin-bottom: 10px;
        }
        
        .tablenav-pages {
            float: right;
        }
        
        .pagination-links {
            margin-left: 10px;
        }
        </style>
        <?php
    }
}
