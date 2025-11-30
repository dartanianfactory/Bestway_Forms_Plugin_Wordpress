<?php
if (!defined('ABSPATH')) exit;

class BestwayForms_Tab_Contacts_Render {
    
    public static function render() {
        ?>
        <div class="card">
            <h2>Контакты и поддержка</h2>
            <p>Связаться с автором плагина для получения поддержки, предложений или сотрудничества.</p>
        </div>
        
        <div class="card">
            <h3>Контактная информация</h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <span class="dashicons dashicons-email" style="color: #0073aa;"></span>
                        Email
                    </th>
                    <td>
                        <a href="mailto:romanwebdev93@gmail.com" class="contact-link">
                            romanwebdev93@gmail.com
                        </a>
                        <p class="description">Основной email для связи по вопросам плагина</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <span class="dashicons dashicons-format-chat" style="color: #0088cc;"></span>
                        Telegram
                    </th>
                    <td>
                        <a href="https://t.me/boontar_mini" target="_blank" class="contact-link">
                            @boontar_mini
                        </a>
                        <p class="description">Быстрая связь через Telegram</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <span class="dashicons dashicons-megaphone" style="color: #ff6b00;"></span>
                        Канал проекта
                    </th>
                    <td>
                        <a href="https://t.me/best_way_shop" target="_blank" class="contact-link">
                            Bestway Forms Channel
                        </a>
                        <p class="description">Новости, обновления и полезные материалы по плагину</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <span class="dashicons dashicons-money" style="color: #28a745;"></span>
                        GitHub
                    </th>
                    <td>
                        <a href="https://github.com/dartanianfactory/Bestway_Forms_Plugin_Wordpress" target="_blank" class="contact-link">
                            GitHub Repository
                        </a>
                        <p class="description">Исходный код, issues и contributions</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="card">
            <h3>Поддержка проекта</h3>
            <p>Если плагин помог вашему бизнесу, рассмотрите возможность поддержать его развитие:</p>
            
            <div class="donation-section">
                <div class="donation-methods">
                    <div class="donation-method">
                        <h4>💳 Банковская карта</h4>
                        <div class="card-number">
                            <code id="card-number">2203 8303 1875 8787</code>
                            <button type="button" class="button button-small copy-btn" data-clipboard-target="#card-number">
                                Копировать
                            </button>
                        </div>
                        <p class="description">Номер карты для переводов</p>
                    </div>
                    
                    <div class="donation-method">
                        <h4>🤝 Коммерческая поддержка</h4>
                        <p>Нужна кастомизация плагина под ваши задачи? Готов реализовать дополнительные функции и интеграции.</p>
                        <a href="mailto:roman.agafonov.dev@gmail.com?subject=Кастомизация BestwayForms" class="button button-primary">
                            Обсудить проект
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h3>О плагине</h3>
            <div class="about-plugin">
                <p><strong>BestwayForms</strong> - это продвинутая система управления формами с интеграциями n8n, AI Manager и WooCommerce.</p>
                
                <div class="features-list">
                    <h4>Основные возможности:</h4>
                    <ul>
                        <li>📧 Умные формы с шаблонами</li>
                        <li>🔗 Интеграция с n8n для автоматизации</li>
                        <li>🤖 AI-анализ лидов</li>
                        <li>🛒 Сбор заказов WooCommerce</li>
                        <li>📊 Дашборд и аналитика</li>
                        <li>📤 Экспорт данных в CSV, Excel, JSON</li>
                    </ul>
                </div>
                
                <div class="version-info">
                    <p><strong>Версия:</strong> <?php echo esc_html(BESTWAY_FORMS_VERSION); ?></p>
                    <p><strong>Разработчик:</strong> Roman Agafonov</p>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('.copy-btn').on('click', function() {
                var target = $(this).data('clipboard-target');
                var text = $(target).text().trim();
                
                var $temp = $('<textarea>');
                $('body').append($temp);
                $temp.val(text).select();
                
                try {
                    var successful = document.execCommand('copy');
                    if (successful) {
                        var $button = $(this);
                        var originalText = $button.text();
                        $button.text('Скопировано!');
                        
                        setTimeout(function() {
                            $button.text(originalText);
                        }, 2000);
                    }
                } catch (err) {
                    console.log('Ошибка копирования: ', err);
                }
                
                $temp.remove();
            });
        });
        </script>

        <style>
        .contact-link {
            font-size: 16px;
            font-weight: 500;
            text-decoration: none;
            color: #0073aa;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .contact-link:hover {
            color: #005a87;
            text-decoration: underline;
        }

        .donation-section {
            margin-top: 20px;
        }

        .donation-methods {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
            margin-top: 20px;
        }

        .donation-method {
            padding: 25px;
            border: 2px solid #f0f0f0;
            border-radius: 8px;
            background: #fafafa;
        }

        .donation-method h4 {
            margin: 0 0 15px 0;
            font-size: 18px;
            color: #23282d;
        }

        .card-number {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin: 15px 0;
        }

        .card-number code {
            background: #fff;
            padding: 12px 16px;
            border-radius: 6px;
            border: 1px solid #ddd;
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 1px;
            color: #333;
        }

        .features-list ul {
            columns: 2;
            column-gap: 30px;
            margin: 15px 0;
        }

        .features-list li {
            margin-bottom: 8px;
            break-inside: avoid;
        }

        .version-info {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .version-info p {
            margin: 5px 0;
        }

        @media (max-width: 768px) {
            .donation-methods {
                grid-template-columns: 1fr;
            }
            
            .features-list ul {
                columns: 1;
            }
        }
        </style>
        <?php
    }
}
