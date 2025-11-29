<div class="wrap">
    <h1>Lead Form Dashboard</h1>
    
    <div class="lead-stats">
        <div class="stat-card">
            <h3>Всего заявок</h3>
            <div class="stat-number"><?php echo $stats['total_leads']; ?></div>
        </div>
        <div class="stat-card">
            <h3>Новые</h3>
            <div class="stat-number"><?php echo $stats['new_leads']; ?></div>
        </div>
        <div class="stat-card">
            <h3>Обработано</h3>
            <div class="stat-number"><?php echo $stats['processed_leads']; ?></div>
        </div>
        <div class="stat-card">
            <h3>Успешных отправок</h3>
            <div class="stat-number"><?php echo $stats['successful_submissions']; ?></div>
        </div>
    </div>
    
    <div class="dashboard-content">
        <div class="chart-section">
            <h2>Статистика заявок</h2>
            <canvas id="leadsChart" width="400" height="200"></canvas>
        </div>
        
        <div class="recent-leads">
            <h2>Последние заявки</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Имя</th>
                        <th>Email</th>
                        <th>Телефон</th>
                        <th>Статус</th>
                        <th>Дата</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_leads as $lead): ?>
                    <tr>
                        <td><?php echo esc_html($lead->post_title); ?></td>
                        <td><?php echo esc_html(get_post_meta($lead->ID, '_lead_email', true)); ?></td>
                        <td><?php echo esc_html(get_post_meta($lead->ID, '_lead_phone', true)); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo esc_attr(get_post_meta($lead->ID, '_lead_status', true)); ?>">
                                <?php echo lead_form_get_status_label(get_post_meta($lead->ID, '_lead_status', true)); ?>
                            </span>
                        </td>
                        <td><?php echo date('d.m.Y H:i', strtotime($lead->post_date)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="integration-info">
            <h2>Об интеграции</h2>
            <div class="info-cards">
                <div class="info-card">
                    <h3>Webhook URL</h3>
                    <code><?php echo esc_html(get_option('lead_form_webhook_url', 'Не настроен')); ?></code>
                </div>
                <div class="info-card">
                    <h3>Статус n8n</h3>
                    <?php
                    $webhook_status = Lead_Form_Admin::check_webhook_status_static();
                    ?>
                    <span class="status-indicator <?php echo $webhook_status ? 'active' : 'inactive'; ?>">
                        <?php echo $webhook_status ? 'Активен' : 'Неактивен'; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>