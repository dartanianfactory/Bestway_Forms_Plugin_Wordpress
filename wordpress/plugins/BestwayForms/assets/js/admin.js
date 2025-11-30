(function($) {
    'use strict';

    class BestwayFormsAdmin {
        constructor() {
            this.init();
        }
        
        init() {
            this.initFormCreation();
            this.initCopyShortcode();
            this.initSettingsTabs();
        }
        
        initFormCreation() {
            console.log('BestwayFormsAdmin initialized');

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
                    url: bestway_forms_admin.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'bestway_create_form',
                        template: currentTemplate,
                        nonce: bestway_forms_admin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            window.location.href = bestway_forms_admin.ajax_url.replace('admin-ajax.php', 'admin.php?page=bestway-forms-list');
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
        }
        
        initCopyShortcode() {
            $(document).on('click', '.copy-shortcode', function() {
                const shortcode = $(this).data('shortcode');
                const $button = $(this);

                const $temp = $('<textarea>');
                $('body').append($temp);
                $temp.val(shortcode).select();
                
                try {
                    document.execCommand('copy');
                    $button.text('Скопировано!');
                    setTimeout(() => {
                        $button.text('Копировать');
                    }, 2000);
                } catch (err) {
                    alert('Не удалось скопировать шорткод');
                }
                
                $temp.remove();
            });
        }
        
        initSettingsTabs() {
            console.log('Bestway settings tabs initialized');
        }
    }

    $(document).ready(() => {
        new BestwayFormsAdmin();
    });

})(jQuery);