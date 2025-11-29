<?php
if (!defined('ABSPATH')) exit;

class BestwayForms_Admin_Create_Form_Render {
    
    public static function render($templates, $edit_form = null) {
        $forms_model = new BestwayForms_Model_Forms();
        ?>
        <div class="wrap">
            <h1><?php echo $edit_form ? 'Редактировать форму' : 'Создать новую форму'; ?></h1>
            
            <?php if ($edit_form): ?>
                <div class="notice notice-info">
                    <p>Редактирование формы: <strong><?php echo esc_html($edit_form->name); ?></strong></p>
                </div>
                
                <div class="edit-form-preview">
                    <h2>Предпросмотр формы</h2>
                    <div class="form-preview">
                        <?php
                        $template_file = BESTWAY_FORMS_PATH . "forms/{$edit_form->template}/form.php";
                        if (file_exists($template_file)) {
                            include $template_file;
                        } else {
                            echo '<p>Шаблон формы не найден.</p>';
                        }
                        ?>
                    </div>
                    <div class="form-shortcode">
                        <h3>Шорткод для вставки:</h3>
                        <code>[bestway_form id="<?php echo esc_attr($edit_form->id); ?>"]</code>
                        <button type="button" class="button copy-shortcode" data-shortcode='[bestway_form id="<?php echo esc_attr($edit_form->id); ?>"]'>
                            Копировать шорткод
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <?php if (empty($templates)): ?>
                    <div class="notice notice-warning">
                        <p>Шаблоны форм не найдены. Создайте шаблоны в папке forms/.</p>
                    </div>
                <?php else: ?>
                    <div class="form-templates-grid">
                        <?php foreach ($templates as $template_key => $template_name): 
                            $template_fields = $forms_model->get_template_fields($template_key);
                        ?>
                            <div class="template-card">
                                <div class="template-preview">
                                    <div class="template-icon">
                                        <span class="dashicons dashicons-email-alt"></span>
                                    </div>
                                    <h3><?php echo esc_html($template_name); ?></h3>
                                    <p>Шаблон: <?php echo esc_html($template_key); ?></p>
                                    <?php if (!empty($template_fields)): ?>
                                        <div class="template-fields">
                                            <small>Поля: <?php echo count($template_fields); ?></small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="template-actions">
                                    <button type="button" 
                                            class="button button-primary create-form-btn" 
                                            data-template="<?php echo esc_attr($template_key); ?>"
                                            data-fields='<?php echo esc_attr(json_encode($template_fields)); ?>'>
                                        Использовать этот шаблон
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div id="create-form-modal" style="display: none;">
                        <div class="modal-content">
                            <h3>Создать форму</h3>
                            <p>Создание формы из шаблона: <strong><span id="selected-template"></span></strong></p>
                            <div class="form-fields-preview">
                                <h4>Обнаруженные поля:</h4>
                                <div id="fields-loading" style="display: none;">
                                    <p>Загрузка полей...</p>
                                </div>
                                <ul id="detected-fields"></ul>
                                <div id="no-fields" style="display: none;">
                                    <p>В шаблоне не обнаружено полей формы.</p>
                                </div>
                            </div>
                            <div class="modal-actions">
                                <button type="button" class="button" id="cancel-create-btn">Отмена</button>
                                <button type="button" class="button button-primary" id="confirm-create-btn">Создать форму</button>
                            </div>
                        </div>
                    </div>
                    
                    <script type="text/javascript">
                    jQuery(document).ready(function($) {
                        let currentTemplate = '';
                        let currentFields = {};
                        
                        $('.create-form-btn').on('click', function(e) {
                            e.preventDefault();
                            currentTemplate = $(this).data('template');
                            currentFields = $(this).data('fields');
                            
                            $('#selected-template').text(currentTemplate);
                            
                            var fieldsList = $('#detected-fields');
                            fieldsList.empty();
                            
                            if (currentFields && Object.keys(currentFields).length > 0) {
                                $.each(currentFields, function(fieldName, fieldConfig) {
                                    var type = fieldConfig.type || 'text';
                                    var required = fieldConfig.required ? ' <span class="required">(обязательное)</span>' : '';
                                    var placeholder = fieldConfig.placeholder ? '<br><small>Placeholder: "' + fieldConfig.placeholder + '"</small>' : '';
                                    
                                    if (fieldConfig.type === 'select' && fieldConfig.options) {
                                        var options = '<br><small>Опции: ' + Object.keys(fieldConfig.options).join(', ') + '</small>';
                                        fieldsList.append('<li><strong>' + fieldName + '</strong> (' + type + ')' + required + placeholder + options + '</li>');
                                    } else {
                                        fieldsList.append('<li><strong>' + fieldName + '</strong> (' + type + ')' + required + placeholder + '</li>');
                                    }
                                });
                                $('#no-fields').hide();
                                fieldsList.show();
                            } else {
                                fieldsList.hide();
                                $('#no-fields').show();
                            }
                            
                            $('#create-form-modal').show();
                        });
                        
                        $('#confirm-create-btn').on('click', function() {
                            const $btn = $(this);
                            $btn.prop('disabled', true).text('Создание...');
                            
                            $.ajax({
                                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                                type: 'POST',
                                data: {
                                    action: 'bestway_create_form',
                                    template: currentTemplate,
                                    nonce: '<?php echo wp_create_nonce('bestway_forms_nonce'); ?>'
                                },
                                success: function(response) {
                                    if (response.success) {
                                        window.location.href = '<?php echo admin_url('admin.php?page=gadzila-forms-list'); ?>';
                                    } else {
                                        alert('Ошибка создания формы: ' + response.data);
                                        $btn.prop('disabled', false).text('Создать форму');
                                    }
                                },
                                error: function(xhr, status, error) {
                                    alert('Ошибка сети: ' + error);
                                    $btn.prop('disabled', false).text('Создать форму');
                                }
                            });
                        });
                        
                        $('#cancel-create-btn').on('click', function() {
                            $('#create-form-modal').hide();
                        });
                        
                        $('#create-form-modal').on('click', function(e) {
                            if (e.target === this) {
                                $(this).hide();
                            }
                        });
                    });
                    </script>
                    
                    <style>
                    .template-fields {
                        margin-top: 10px;
                        padding: 5px;
                        background: #f8f9fa;
                        border-radius: 4px;
                        font-size: 12px;
                    }
                    
                    #detected-fields {
                        max-height: 200px;
                        overflow-y: auto;
                        margin: 10px 0;
                    }
                    
                    #detected-fields li {
                        padding: 8px;
                        margin-bottom: 5px;
                        background: white;
                        border-radius: 4px;
                        border-left: 3px solid #0073aa;
                    }
                    
                    .required {
                        color: #d63638;
                        font-weight: bold;
                    }
                    
                    #detected-fields small {
                        color: #666;
                        font-style: italic;
                    }
                    </style>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }
}