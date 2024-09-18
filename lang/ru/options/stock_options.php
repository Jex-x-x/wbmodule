<?php
$suffix = strtoupper(basename(dirname(__DIR__, 3)));

$MESS[$suffix.".TAB_NAME"] = "Обновление остатков";

$MESS[$suffix.".CURL_NOTE"] = "<div style='color:red;'><strong>Внимание!</strong> У вас не установлено расширение Curl, модуль работать не будет!</div>";
$MESS[$suffix.".MODULES_ERROR"] = "<div style='color:red;'><strong>Внимание!</strong> Не установлены необходимые модули Битрикс! Модуль работать не будет!</div>";

$MESS[$suffix.".STOCKS_AGENT_HEAD"] = "Обновление остатков";
$MESS[$suffix.".STOCKS_SCHEDULER_IS_ON"] = "Включить авто-обновление остатков";
$MESS[$suffix.".SCHEDULER_INTERVAL"] = "Интервал авто-запуска, минут";
$MESS[$suffix.".SELECT_STOCK_TYPE_HINT"] = "В данной опции необходимо выбрать, где у вас хранятся остатки товаров. Остатки из выбранного вами места будут передаваться на маркетплейс.";
$MESS[$suffix.".SELECT_STOCK_TYPE"] = "Откуда брать данные об остатках";
$MESS[$suffix.".STOCKS_MINIMAL"] = "Минимальное разрешенное кол-во на остатке";
$MESS[$suffix.".RESET_STOCK"] = 'Обнулять остатки для товаров, которых нет в Битриксе';
$MESS[$suffix.".STOCKS_HINT"] =
    "<div style='text-align:left'>"
        ."<div><div style='color:red;'>Внимание!</div> При включённой опции \"Обнулять остатки товаров, которых нет в Битрикс\", товары на Wildberries, не найденные в Битрикс, будут обнулены на Wildberries.</div>"
        ."<div>Поиск соответсвия товаров ведется по установленным опциям:</div>"
        ."<ul><li>Артикул в Wildberries (для товара, комплекта или набора)</li><li>Артикул в Wildberries (для торгового предложения)</li></ul>"
    ."</div>"
;

$MESS[$suffix.".STOCKS_MINIMAL_NOTE"] = "Если остаток на сайте меньше минимального разрешенного значения, то в Wildberries будет выгружен остаток 0";
$MESS[$suffix.".WAREHOUSE_ID"] = "ID склада на Wildberries";
$MESS[$suffix.".LINKED_ACCOUNTS_HINT"] = "Для товаров, которые присутствуют на связанных профилях, не сбрасываются остатки";
$MESS[$suffix.".LINKED_ACCOUNTS"] = "Связанные профили";
$MESS[$suffix.".ACCOUNT"] = "Профиль";
$MESS[$suffix.".STOCKS_NOTE"] =
    "<div style='text-align:left;'>Если ID склада не указан, будет выбран первый доступный склад Wildberries<br>"
    ."<span style='color:red;'><strong>Внимание!</strong> Обновление остатков доступно только при идентификации товаров по штрихкодам</span></div>"
;

$MESS[$suffix.".PRODUCT_STOCK_PROPERTY"] = 'Остаток из свойства товара';
$MESS[$suffix.".OFFER_STOCK_PROPERTY"] = 'Остаток из свойства ТП';

$MESS[$suffix.".ADD_ACCOUNT"] = "Добавить аккаунт";

$MESS[$suffix.".SERVICE_MSG"] = "<div class='wbs24_wbapi_service_msg'><img src='/bitrix/images/wbs24.wbapi/logo.png' alt='Логотип WEB Симфония' style='padding: 0 10px 0 0;'></div>";

$MESS[$suffix.".SKU_NOTE"] =
    '<div style="text-align:left;"><b>Внимание!</b> Перед включением обновления остатков убедитесь, что для следующих опций, выставлены верные значения: <ul><li><b>Артикул в Wildberries (для товара, комплекта или набора)</b></li><li><b>Артикул в Wildberries (для торгового предложения)</b></li></ul>'
    .'Найти эти опции можно по пути: Wildberries > Аккаунт [номер_аккаунта] > Заказы.</div>'
;

$MESS[$suffix.".WARNING_NOTE"] =
    '<div><span style="color:red;">Внимание!</span> Не выбран сайт. Сначала выберите сайт по пути: Wildberries > Профиль [номер_аккаунта] > Заказы.</div>'
    .'<div>После этого переходите к настройке <span style="color:red;">обновления остатков</span>.</div>'
;


