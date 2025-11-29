<?php
if (!defined('ABSPATH')) exit;
?>
<div class="contact-form-template">
    <div class="form-field">
        <label for="name">Имя *</label>
        <input type="text" id="name" name="name" required placeholder="Введите ваше имя">
        <div class="error-message"></div>
    </div>
    
    <div class="form-field">
        <label for="email">Email *</label>
        <input type="email" id="email" name="email" required placeholder="Введите ваш email">
        <div class="error-message"></div>
    </div>
    
    <div class="form-field">
        <label for="phone">Телефон</label>
        <input type="tel" id="phone" name="phone" placeholder="+7 (999) 999-99-99">
        <div class="error-message"></div>
    </div>
    
    <div class="form-field">
        <label for="company">Компания</label>
        <input type="text" id="company" name="company" placeholder="Название компании">
        <div class="error-message"></div>
    </div>
    
    <div class="form-field">
        <label for="service_type">Тип услуги</label>
        <select id="service_type" name="service_type">
            <option value="">Выберите услугу</option>
            <option value="consultation">Консультация</option>
            <option value="development">Разработка</option>
            <option value="support">Поддержка</option>
        </select>
        <div class="error-message"></div>
    </div>
    
    <div class="form-field">
        <label for="message">Сообщение</label>
        <textarea id="message" name="message" rows="4" placeholder="Опишите вашу задачу..."></textarea>
        <div class="error-message"></div>
    </div>
    
    <div class="form-field checkbox-field">
        <input type="checkbox" id="newsletter" name="newsletter" value="1">
        <label for="newsletter">Подписаться на рассылку</label>
        <div class="error-message"></div>
    </div>
</div>