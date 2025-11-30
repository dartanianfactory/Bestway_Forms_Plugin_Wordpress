<?php
if (!defined('ABSPATH')) exit;

class BestwayForms_Integration_SMTP {
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        $this->register_hooks();
    }
    
    private function register_hooks() {
        add_action('phpmailer_init', [$this, 'configure_smtp']);
        add_action('bestway_forms_lead_created', [$this, 'send_lead_notification'], 10, 2);
    }
    
    public function configure_smtp($phpmailer) {
        $smtp_host = get_option('bestway_forms_smtp_host');
        $smtp_port = get_option('bestway_forms_smtp_port');
        $smtp_encryption = get_option('bestway_forms_smtp_encryption');
        $smtp_username = get_option('bestway_forms_smtp_username');
        $smtp_password = get_option('bestway_forms_smtp_password');
        
        if (empty($smtp_host) || empty($smtp_username)) {
            return;
        }
        
        $phpmailer->isSMTP();
        $phpmailer->Host = $smtp_host;
        $phpmailer->SMTPAuth = true;
        $phpmailer->Port = $smtp_port ?: 587;
        $phpmailer->Username = $smtp_username;
        $phpmailer->Password = $smtp_password;
        
        if ($smtp_encryption === 'ssl') {
            $phpmailer->SMTPSecure = 'ssl';
        } elseif ($smtp_encryption === 'tls') {
            $phpmailer->SMTPSecure = 'tls';
        }

        $from_email = get_option('bestway_forms_smtp_from_email');
        $from_name = get_option('bestway_forms_smtp_from_name');
        
        if (!empty($from_email)) {
            $phpmailer->From = $from_email;
        }
        
        if (!empty($from_name)) {
            $phpmailer->FromName = $from_name;
        }
    }
    
    public function send_lead_notification($lead_data, $is_wc_lead = false) {
        if (!get_option('bestway_forms_smtp_notifications_enabled')) {
            return;
        }
        
        $lead_types = get_option('bestway_forms_smtp_lead_types', 'all');
        $recipient_emails = get_option('bestway_forms_smtp_recipient_emails');
        
        if (empty($recipient_emails)) {
            return;
        }

        if ($lead_types === 'forms_only' && $is_wc_lead) {
            return;
        }
        
        if ($lead_types === 'woocommerce_only' && !$is_wc_lead) {
            return;
        }
        
        $emails = array_map('trim', explode(',', $recipient_emails));
        $subject = get_option('bestway_forms_smtp_email_subject', 'Новый лид с сайта');
        
        if ($is_wc_lead) {
            $message = $this->prepare_wc_lead_email($lead_data);
            $subject = 'Новый заказ: #' . ($lead_data['order_id'] ?? '');
        } else {
            $message = $this->prepare_form_lead_email($lead_data);
        }
        
        $headers = array(
            'From: ' . get_option('bestway_forms_smtp_from_name') . ' <' . get_option('bestway_forms_smtp_from_email') . '>',
            'Content-Type: text/html; charset=UTF-8'
        );
        
        foreach ($emails as $email) {
            if (is_email($email)) {
                wp_mail($email, $subject, $message, $headers);
            }
        }
    }
    
    private function prepare_form_lead_email($lead_data) {
        $forms_model = new BestwayForms_Model_Forms();
        $form = $forms_model->get_form($lead_data['form_id']);
        $form_data = $lead_data;
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #0073aa; color: white; padding: 20px; text-align: center; }
                .content { background: #f9f9f9; padding: 20px; }
                .field { margin-bottom: 10px; }
                .field-label { font-weight: bold; }
                .footer { background: #f1f1f1; padding: 10px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Новый лид с формы</h1>
                </div>
                <div class="content">
                    <p><strong>Форма:</strong> <?php echo esc_html($form ? $form->name : 'Форма #' . $lead_data['form_id']); ?></p>
                    <p><strong>Время:</strong> <?php echo current_time('d.m.Y H:i'); ?></p>
                    
                    <h3>Данные лида:</h3>
                    <?php
                    foreach ($form_data as $key => $value) {
                        if (!in_array($key, ['form_id', 'action', 'bestway_nonce'])) {
                            echo '<div class="field">';
                            echo '<span class="field-label">' . esc_html(ucfirst(str_replace('_', ' ', $key))) . ':</span> ';
                            echo esc_html($value);
                            echo '</div>';
                        }
                    }
                    ?>
                </div>
                <div class="footer">
                    <p>Это письмо отправлено автоматически с сайта <?php echo get_bloginfo('name'); ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    private function prepare_wc_lead_email($lead_data) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #0073aa; color: white; padding: 20px; text-align: center; }
                .content { background: #f9f9f9; padding: 20px; }
                .field { margin-bottom: 10px; }
                .field-label { font-weight: bold; }
                .products { margin-top: 15px; }
                .product { background: white; padding: 10px; margin-bottom: 5px; border-left: 3px solid #0073aa; }
                .footer { background: #f1f1f1; padding: 10px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Новый заказ WooCommerce</h1>
                </div>
                <div class="content">
                    <p><strong>Номер заказа:</strong> #<?php echo esc_html($lead_data['order_id'] ?? ''); ?></p>
                    <p><strong>Время:</strong> <?php echo current_time('d.m.Y H:i'); ?></p>
                    
                    <h3>Информация о клиенте:</h3>
                    <div class="field">
                        <span class="field-label">Имя:</span> 
                        <?php echo esc_html(($lead_data['first_name'] ?? '') . ' ' . ($lead_data['last_name'] ?? '')); ?>
                    </div>
                    <div class="field">
                        <span class="field-label">Email:</span> 
                        <?php echo esc_html($lead_data['email'] ?? ''); ?>
                    </div>
                    <div class="field">
                        <span class="field-label">Телефон:</span> 
                        <?php echo esc_html($lead_data['phone'] ?? ''); ?>
                    </div>
                    
                    <h3>Детали заказа:</h3>
                    <div class="field">
                        <span class="field-label">Сумма:</span> 
                        <?php echo esc_html($lead_data['order_total'] ?? ''); ?>
                    </div>
                    <div class="field">
                        <span class="field-label">Статус:</span> 
                        <?php echo esc_html($lead_data['order_status'] ?? ''); ?>
                    </div>
                    
                    <?php if (!empty($lead_data['items'])): ?>
                    <div class="products">
                        <h4>Товары:</h4>
                        <?php foreach ($lead_data['items'] as $item): ?>
                            <div class="product">
                                <strong><?php echo esc_html($item['product_name'] ?? $item['name'] ?? ''); ?></strong><br>
                                Количество: <?php echo esc_html($item['quantity'] ?? ''); ?><br>
                                Цена: <?php echo esc_html($item['total'] ?? $item['price'] ?? ''); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="footer">
                    <p>Это письмо отправлено автоматически с сайта <?php echo get_bloginfo('name'); ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}
