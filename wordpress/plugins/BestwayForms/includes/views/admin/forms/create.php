<?php
if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1>Create New Form</h1>
    
    <div class="bestway-forms-create">
        <div class="form-templates-grid">
            <?php foreach ($templates as $template_key => $template_name): ?>
                <div class="template-card">
                    <div class="template-preview">
                        <div class="template-icon">
                            <span class="dashicons dashicons-email-alt"></span>
                        </div>
                        <h3><?php echo esc_html($template_name); ?></h3>
                        <p>Template: <?php echo esc_html($template_key); ?></p>
                    </div>
                    <div class="template-actions">
                        <button type="button" 
                                class="button button-primary create-form-btn" 
                                data-template="<?php echo esc_attr($template_key); ?>">
                            Use This Template
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div id="create-form-modal" style="display: none;">
            <div class="modal-content">
                <h3>Create Form</h3>
                <p>Creating form from template: <span id="selected-template"></span></p>
                <div class="form-fields-preview">
                    <h4>Detected Fields:</h4>
                    <ul id="detected-fields"></ul>
                </div>
                <div class="modal-actions">
                    <button type="button" class="button" id="cancel-create-btn">Cancel</button>
                    <button type="button" class="button button-primary" id="confirm-create-btn">Create Form</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    let currentTemplate = '';
    
    $('.create-form-btn').on('click', function(e) {
        e.preventDefault();
        currentTemplate = $(this).data('template');
        console.log('Selected template:', currentTemplate);
        
        $('#selected-template').text(currentTemplate);
        
        // Показываем стандартные поля
        $('#detected-fields').html(`
            <li>name</li>
            <li>email</li>
            <li>phone</li>
            <li>message</li>
        `);
        
        $('#create-form-modal').show();
    });

    $('#confirm-create-btn').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true).text('Creating...');
        console.log('Creating form from template:', currentTemplate);
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'bestway_create_form',
                template: currentTemplate,
                nonce: '<?php echo wp_create_nonce('bestway_forms_nonce'); ?>'
            },
            success: function(response) {
                console.log('AJAX response:', response);
                if (response.success) {
                    window.location.href = '<?php echo admin_url('admin.php?page=bestway-forms-list'); ?>';
                } else {
                    alert('Error creating form: ' + response.data);
                    $btn.prop('disabled', false).text('Create Form');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                alert('Network error occurred: ' + error);
                $btn.prop('disabled', false).text('Create Form');
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