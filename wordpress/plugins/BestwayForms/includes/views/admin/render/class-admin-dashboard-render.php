<?php
if (!defined('ABSPATH')) exit;

class BestwayForms_Admin_Dashboard_Render {
    
    public static function render($stats) {
        ?>
        <div class="wrap">
            <h1>BestwayForms Дашборд</h1>
            
            <div class="lead-stats">
                <div class="stat-card">
                    <h3>Всего форм</h3>
                    <div class="stat-number"><?php echo esc_html($stats['total_forms']); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Всего лидов</h3>
                    <div class="stat-number"><?php echo esc_html($stats['total_leads']); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Лиды с форм</h3>
                    <div class="stat-number"><?php echo esc_html($stats['form_leads']); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Лиды с WooCommerce</h3>
                    <div class="stat-number"><?php echo esc_html($stats['wc_leads']); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Новые лиды</h3>
                    <div class="stat-number"><?php echo esc_html($stats['new_leads']); ?></div>
                </div>
            </div>
            
            <div class="integration-status">
                <h2>Статус интеграций</h2>
                <div class="status-cards">
                    <?php
                    $n8n_status = get_option('bestway_forms_n8n_enabled') ? 'Активна' : 'Неактивна';
                    $ai_status = get_option('bestway_forms_ai_enabled') ? 'Активна' : 'Неактивна';
                    $wc_status = get_option('bestway_forms_wc_enabled') ? 'Активна' : 'Неактивна';
                    ?>
                    <div class="status-card <?php echo get_option('bestway_forms_n8n_enabled') ? 'active' : 'inactive'; ?>">
                        <h3>n8n Интеграция</h3>
                        <div class="status"><?php echo esc_html($n8n_status); ?></div>
                    </div>
                    
                    <div class="status-card <?php echo get_option('bestway_forms_ai_enabled') ? 'active' : 'inactive'; ?>">
                        <h3>AI Менеджер</h3>
                        <div class="status"><?php echo esc_html($ai_status); ?></div>
                    </div>
                    
                    <div class="status-card <?php echo get_option('bestway_forms_wc_enabled') ? 'active' : 'inactive'; ?>">
                        <h3>WooCommerce</h3>
                        <div class="status"><?php echo esc_html($wc_status); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="recent-leads">
                <h2>Последние лиды</h2>
                <?php if (!empty($stats['recent_leads'])): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Источник</th>
                                <th>Данные</th>
                                <th>Статус</th>
                                <th>Дата</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['recent_leads'] as $lead): ?>
                                <tr>
                                    <td><?php echo esc_html($lead->id); ?></td>
                                    <td>
                                        <?php 
                                        if (isset($lead->source) && $lead->source === 'woocommerce') {
                                            echo 'WooCommerce';
                                        } else {
                                            echo 'Форма #' . (isset($lead->form_id) ? esc_html($lead->form_id) : 'N/A');
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        if (isset($lead->form_data)) {
                                            $form_data = json_decode($lead->form_data, true);
                                            if (isset($lead->source) && $lead->source === 'woocommerce') {
                                                echo 'Заказ #' . (isset($form_data['order_id']) ? esc_html($form_data['order_id']) : 'N/A');
                                            } else {
                                                echo isset($form_data['name']) ? esc_html($form_data['name']) : (isset($form_data['email']) ? esc_html($form_data['email']) : 'Нет данных');
                                            }
                                        } else {
                                            echo 'Нет данных';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if (isset($lead->status)): ?>
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
                                        <?php else: ?>
                                            <span class="status-badge">Неизвестно</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if (isset($lead->created_at)) {
                                            echo esc_html(date('d.m.Y H:i', strtotime($lead->created_at)));
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Лидов пока нет.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
