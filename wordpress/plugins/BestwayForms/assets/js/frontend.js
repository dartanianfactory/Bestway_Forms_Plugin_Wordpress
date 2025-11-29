(function($) {
    'use strict';

    class BestwayFormsFrontend {
        constructor() {
            this.init();
        }
        
        init() {
            this.handleFormSubmit();
        }
        
        handleFormSubmit() {
            $(document).on('submit', '.gadzila-form', (e) => {
                e.preventDefault();
                
                const $form = $(e.target);
                const $submitBtn = $form.find('.gadzila-submit-btn');
                const $messages = $form.closest('.gadzila-form-wrapper').find('.gadzila-form-messages');

                $messages.hide().removeClass('success error');
                $form.find('.error-message').hide();
                
                $submitBtn.prop('disabled', true).text('Отправка...');

                const formData = new FormData($form[0]);
                
                $.ajax({
                    url: bestway_forms_frontend.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: (response) => {
                        if (response.success) {
                            this.showMessage($messages, response.data, 'success');
                            $form[0].reset();
                        } else {
                            this.showMessage($messages, response.data, 'error');

                            if (typeof response.data === 'object') {
                                this.showFieldErrors(response.data);
                            }
                        }
                    },
                    error: (xhr, status, error) => {
                        this.showMessage($messages, 'Ошибка сети: ' + error, 'error');
                    },
                    complete: () => {
                        $submitBtn.prop('disabled', false).text('Отправить');
                    }
                });
            });
        }
        
        showMessage($element, message, type) {
            $element
                .removeClass('success error')
                .addClass(type)
                .text(message)
                .show();
        }
        
        showFieldErrors(errors) {
            $.each(errors, (field, message) => {
                const $field = $(`[name="${field}"]`);
                const $error = $field.closest('.form-field').find('.error-message');
                
                if ($error.length) {
                    $error.text(message).show();
                }
            });
        }
    }

    $(document).ready(() => {
        new BestwayFormsFrontend();
    });

})(jQuery);
