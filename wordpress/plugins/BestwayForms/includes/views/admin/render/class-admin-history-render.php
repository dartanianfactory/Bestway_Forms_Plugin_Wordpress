<?php
if (!defined('ABSPATH')) exit;

class BestwayForms_Admin_History_Render {
    
    public static function render($history, $current_page = 1, $total_pages = 1, $total_items = 0) {
        $per_page = 50;
        ?>
        <div class="wrap">
            <h1>История обработки (<?php echo $total_items; ?>)</h1>
            
            <div class="tablenav top">
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
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Тип</th>
                        <th>Источник</th>
                        <th>RAW Данные</th>
                        <th>n8n RAW</th>
                        <th>AI RAW</th>
                        <th>Дата</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $item): 
                        $is_wc = (isset($item->source) && $item->source === 'woocommerce') || isset($item->order_id);

                        $raw_data = '';
                        if (isset($item->form_data)) {
                            $raw_data = is_string($item->form_data) ? $item->form_data : wp_json_encode($item->form_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                        } elseif (isset($item->customer_data)) {
                            $raw_data = is_string($item->customer_data) ? $item->customer_data : wp_json_encode($item->customer_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                        }
                        
                        $n8n_data = $item->n8n_response ?? '';
                        $ai_data = $item->ai_processed_data ?? '';

                        if ($n8n_data && !is_string($n8n_data)) {
                            $n8n_data = wp_json_encode($n8n_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                        }
                        
                        if ($ai_data && !is_string($ai_data)) {
                            $ai_data = wp_json_encode($ai_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                        }
                    ?>
                        <tr>
                            <td><?php echo esc_html($item->id); ?></td>
                            <td>
                                <?php if ($is_wc): ?>
                                    <span class="dashicons dashicons-cart"></span> WooCommerce
                                <?php else: ?>
                                    <span class="dashicons dashicons-email-alt"></span> Форма
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                if ($is_wc) {
                                    echo 'Заказ #' . (isset($item->order_id) ? esc_html($item->order_id) : 'N/A');
                                } else {
                                    echo 'Форма #' . (isset($item->form_id) ? esc_html($item->form_id) : 'N/A');
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($raw_data): ?>
                                    <button type="button" class="button button-small view-raw" 
                                            data-content="<?php echo esc_attr($raw_data); ?>"
                                            data-title="RAW Данные формы">
                                        Просмотр
                                    </button>
                                <?php else: ?>
                                    <span class="dashicons dashicons-no" title="Нет данных"></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($n8n_data): ?>
                                    <button type="button" class="button button-small view-raw" 
                                            data-content="<?php echo esc_attr($n8n_data); ?>"
                                            data-title="n8n RAW Данные">
                                        Просмотр
                                    </button>
                                <?php else: ?>
                                    <span class="dashicons dashicons-no" title="Нет данных"></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($ai_data): ?>
                                    <button type="button" class="button button-small view-raw" 
                                            data-content="<?php echo esc_attr($ai_data); ?>"
                                            data-title="AI RAW Данные">
                                        Просмотр
                                    </button>
                                <?php else: ?>
                                    <span class="dashicons dashicons-no" title="Нет данных"></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html(date('d.m.Y H:i', strtotime($item->created_at))); ?></td>
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
        </div>
        
        <div id="raw-modal" style="display: none;">
            <div class="modal-content">
                <h3 id="raw-modal-title">RAW Данные</h3>
                <pre id="raw-modal-content" style="background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto; max-height: 400px; white-space: pre-wrap; word-wrap: break-word;"></pre>
                <div class="modal-actions">
                    <button type="button" class="button" id="close-raw-modal">Закрыть</button>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('.view-raw').on('click', function() {
                var content = $(this).data('content');
                var title = $(this).data('title');

                try {
                    if (typeof content === 'object') {
                        var formattedContent = JSON.stringify(content, null, 2);
                    } else {
                        var parsedContent = JSON.parse(content);
                        var formattedContent = JSON.stringify(parsedContent, null, 2);
                    }
                } catch (e) {
                    var formattedContent = content;
                }
                
                $('#raw-modal-title').text(title);
                $('#raw-modal-content').text(formattedContent);
                $('#raw-modal').show();
            });
            
            $('#close-raw-modal').on('click', function() {
                $('#raw-modal').hide();
            });
            
            $('#raw-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).hide();
                }
            });

            $(document).on('click', '#raw-modal-content', function() {
                var content = $(this).text();
                var $temp = $('<textarea>');
                $('body').append($temp);
                $temp.val(content).select();
                document.execCommand('copy');
                $temp.remove();

                var $notice = $('<div class="notice notice-success is-dismissible" style="margin: 10px 0; padding: 5px 10px;"><p>Текст скопирован в буфер обмена</p></div>');
                $('#raw-modal .modal-content').prepend($notice);
                setTimeout(function() {
                    $notice.fadeOut();
                }, 2000);
            });
        });
        </script>
        
        <style>
        #raw-modal {
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
        
        #raw-modal .modal-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            max-width: 800px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        #raw-modal-content {
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        #raw-modal-content:hover {
            background-color: #e9e9e9 !important;
        }
        
        .modal-actions {
            margin-top: 20px;
            text-align: right;
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
