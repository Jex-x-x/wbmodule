<?php
$suffix = strtoupper(basename(dirname(__DIR__, 3)));

$MESS[$suffix.".TAB_NAME"] = "Обновление цен";

$MESS[$suffix.".CURL_NOTE"] = "<div style='color:red;'><strong>Внимание!</strong> У вас не установлено расширение Curl, модуль работать не будет!</div>";
$MESS[$suffix.".MODULES_ERROR"] = "<div style='color:red;'><strong>Внимание!</strong> Не установлены необходимые модули Битрикс! Модуль работать не будет!</div>";

$MESS[$suffix.".PRICES_AGENT_HEAD"] = "Обновление цен";
$MESS[$suffix.".PRICES_SCHEDULER_IS_ON"] = "Включить авто-обновление цен";
$MESS[$suffix.".SCHEDULER_INTERVAL"] = "Интервал авто-запуска, минут";
$MESS[$suffix.".PRICE"] = "<b>Цена</b>";
$MESS[$suffix.".PRICE_NOTE"] =
    "<div style='text-align:left;'>"
    ."<div>В ценообразовании по формуле можно использовать:</div>"
    ."<div>1) метки цен, которые доступны для выбора в каждой формулой</div>"
    ."<div>2) метки свойств для простых товаров и ТП, которые доступны для выбора в каждой формулой</div>"
    ."<div>3) арифметические действия * / + - и скобки для указания приоритета ( )</div>"
    ."<div>4) числа, включая дробные (разделителем десятичной дроби является точка)</div>"
;
$MESS[$suffix.".DISCOUNT"] = "Размер скидки (%)";
$MESS[$suffix.".DISCOUNT_HINT"] = "Введите размер скидки в процентах.";
$MESS[$suffix.".WARNING_NOTE"] =
    '<div><span style="color:red;">Внимание!</span> Не выбран сайт. Сначала выберите сайт по пути: Wildberries > Профиль [номер_аккаунта] > Заказы.</div>'
    .'<div>После этого переходите к настройке <span style="color:red;">обновления цен</span>.</div>'
;
$MESS[$suffix.".PRICE_HINT"] = 'Выберите метки цен / свойств для обновления цены товаров.';
$MESS[$suffix.".PRODUCTS"] = 'Простые товары';
$MESS[$suffix.".OFFERS"] = 'Торговые предложения';

$MESS[$suffix.".ADD_ACCOUNT"] = "Добавить аккаунт";

$MESS[$suffix.".SERVICE_MSG"] = "<div class='wbs24_wbapi_service_msg'><img src='/bitrix/images/wbs24.wbapi/logo.png' alt='Логотип WEB Симфония' style='padding: 0 10px 0 0;'></div>";

$MESS[$suffix.".SKU_NOTE"] =
    '<div style="text-align:left;"><b>Внимание!</b> Перед включением обновления цен убедитесь, что для следующих опций, выставлены верные значения: <ul><li><b>Артикул в Wildberries (для товара, комплекта или набора)</b></li><li><b>Артикул в Wildberries (для торгового предложения)</b></li></ul>'
    .'Найти эти опции можно по пути: Wildberries > Аккаунт [номер_аккаунта] > Заказы.</div>'
;

