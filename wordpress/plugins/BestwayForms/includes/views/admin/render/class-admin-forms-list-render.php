<?php
if (!defined('ABSPATH')) exit;

class BestwayForms_Admin_Forms_List_Render {
    
    public static function render($forms) {
        ?>
        <div class="wrap">
            <h1>Управление формами</h1>
            
            <?php if (isset($_GET['deleted'])): ?>
                <div class="notice notice-success"><p>Форма успешно удалена.</p></div>
            <?php elseif (isset($_GET['error'])): ?>
                <div class="notice notice-error"><p>Ошибка при удалении формы.</p></div>
            <?php endif; ?>
            
            <?php if (empty($forms)): ?>
                <div class="notice notice-info">
                    <p>У вас пока нет созданных форм. <a href="<?php echo admin_url('admin.php?page=gadzila-forms-create'); ?>">Создайте первую форму</a>.</p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Название</th>
                            <th>Шаблон</th>
                            <th>Шорткод</th>
                            <th>Дата создания</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($forms as $form): ?>
                            <tr>
                                <td><?php echo esc_html($form->id); ?></td>
                                <td><?php echo esc_html($form->name); ?></td>
                                <td><?php echo esc_html($form->template); ?></td>
                                <td>
                                    <code>[bestway_form id="<?php echo esc_attr($form->id); ?>"]</code>
                                    <button type="button" class="button button-small copy-shortcode" data-shortcode='[bestway_form id="<?php echo esc_attr($form->id); ?>"]'>
                                        Копировать
                                    </button>
                                </td>
                                <td><?php echo esc_html(date('d.m.Y H:i', strtotime($form->created_at))); ?></td>
                                <td>
                                    <div class="form-actions">
                                        <a href="<?php echo admin_url('admin.php?page=gadzila-forms-create&edit=' . $form->id); ?>" class="button button-primary">
                                            Редактировать
                                        </a>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=gadzila-forms-list&action=delete&form_id=' . $form->id), 'delete_form_' . $form->id); ?>" 
                                           class="button button-link-delete" 
                                           onclick="return confirm('Вы уверены что хотите удалить эту форму?')">
                                            Удалить
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }
}
