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
            $('.gadzila-forms-settings .settings-content > .tab-content').hide();
            $('.gadzila-forms-settings .settings-content > .tab-content:first').show();
            
            $('.gadzila-forms-settings .nav-tab').on('click', function(e) {
                e.preventDefault();
                const $tab = $(this);
                const target = $tab.attr('href');
                
                if (!target || target === '#') return;

                $('.nav-tab').removeClass('nav-tab-active');
                $('.settings-content > .tab-content').hide();

                $tab.addClass('nav-tab-active');
                $(target).show();
                
                const urlParams = new URLSearchParams(window.location.search);
                urlParams.set('tab', target.substring(1));
                window.history.replaceState({}, '', `${window.location.pathname}?${urlParams}`);
            });

            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('tab');
            if (activeTab) {
                $(`.nav-tab[href="#${activeTab}"]`).click();
            }
        }
    }

    // Инициализация когда DOM готов
    $(document).ready(() => {
        new BestwayFormsAdmin();
    });

})(jQuery);
