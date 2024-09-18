<?php
$suffix = strtoupper(basename(dirname(__DIR__, 3)));

$MESS[$suffix.".NEW_SETTINGS_VIEW"] = "Внимание! У нас обновился интерфейс настроек! Скоро мы перейдем на него полностью. ";
$MESS[$suffix.".NEW_SETTINGS_BTN"] = "Посмотреть его можно здесь.";

$MESS[$suffix.".SETTINGS"] = "Настройки №";
$MESS[$suffix.".TITLE"] = "Настройки аккаунта №";
$MESS[$suffix.".RIGHTS"] = "Права доступа";
$MESS[$suffix.".RIGHTS_TITLE"] = "Настройка прав доступа";

$MESS[$suffix.".CURL_NOTE"] = "<div style='color:red;'><strong>Внимание!</strong> У вас не установлено расширение Curl, модуль работать не будет!</div>";
$MESS[$suffix.".MODULES_ERROR"] = "<div style='color:red;'><strong>Внимание!</strong> Не установлены необходимые модули Битрикс! Модуль работать не будет!</div>";

$MESS[$suffix.".MAIN_HEAD"] = "Подключение к API Wildberries";
$MESS[$suffix.".API_KEY"] = "Уникальный ключ (API Key) из личного кабинета Wildberries";
$MESS[$suffix.".API_NOTE"] =
    "<div style='color:red;'><strong>Внимание!</strong> Введите (API Key), иначе данный профиль не будет работать!</div>"
;
$MESS[$suffix.".CLIENT_ID"] = "Клиентский идентификатор (Client ID) из личного кабинета Wildberries";
$MESS[$suffix.".NUMBER_OF_DAYS_TO_PROCESS_ORDERS"] = "Количество дней, за которые будут обрабатываться статусы у заказов";
$MESS[$suffix.".TEST_MODE"] = "Тестовый режим";
$MESS[$suffix.".SAVE_API_LOG"] = "Сохранять логи запросов к API Wildberries";
$MESS[$suffix.".LOG_FOR_DOWNLOAD"] = "Логи для скачивания";
$MESS[$suffix.".LOG_FOR_NO_DOWNLOAD"] = "отсутствуют";
$MESS[$suffix.".CLEAR_LOG"] = "Очистка логов";
$MESS[$suffix.".CLEAR_LOG_BUTTON_NAME"] = "Очистить логи";
$MESS[$suffix.".LOG_HINT_1"] = "Вы можете скачать логи к себе на компьютер для детального анализа.";
$MESS[$suffix.".LOG_HINT_2"] = "Активация опции очистки логов удалит все логи, находящиеся в директории /bitrix/tools/wbs24.wbapi/logs/.";

$MESS[$suffix.".AGENT_HEAD"] = "Авто-запуск загрузки заказов";
$MESS[$suffix.".SCHEDULER_IS_ON"] = "Включить авто-запуск загрузки заказов";
$MESS[$suffix.".SCHEDULER_INTERVAL"] = "Интервал авто-запуска, минут";
$MESS[$suffix.".IS_HTTPS"] = "Протокол передачи данных";
$MESS[$suffix.".AGENT_NOTE"] =
    "<div style='color:red;'><strong>Внимание!</strong> Проверьте и заполните обязательные поля: <ul style='text-align:left;'><li>Интервал авто-запуска, минут</li><li>Протокол передачи данных</li></ul></div>"
;

$MESS[$suffix.".RFBS_HEAD"] = "Настройки realFBS системы";
$MESS[$suffix.".RFBS"] = "Используется система realFBS";

$MESS[$suffix.".SITE_HEAD"] = "Данные для создания заказов";
$MESS[$suffix.".SITE_ID"] = "ID сайта";
$MESS[$suffix.".USER_ID"] = "ID пользователя, под которым будет создаваться заказ";
$MESS[$suffix.".CUSTOMER_ID"] = "ID профиля пользователя, под которым будет создаваться заказ";
$MESS[$suffix.".USER_NOTE"] =
    "<div style='text-align:left;'><strong>Внимание!</strong> Если вы не заполните настройку 'ID пользователя', заказ автоматически будет создаваться под пользователем с ID = 1</div>"
;
$MESS[$suffix.".ALLOW_COMBINE_ORDERS"] = "Разрешить группировать заказы";
$MESS[$suffix.".PERSON_TYPE_ID"] = "Тип плательщика";
$MESS[$suffix.".DELIVERY_SERVICE_ID"] = "Служба доставки";
$MESS[$suffix.".PAYMENT_SYSTEM_ID"] = "Платежная система";
$MESS[$suffix.".NOT_USE_AUTO_PAY"] = "Не ставить флаг оплаты при создании заказа";
$MESS[$suffix.".PROPERTY_OF_EXTERNAL_ORDER_NUMBER"] = "Свойство заказа (Внешний номер заказа)";
$MESS[$suffix.".PROPERTY_OF_SHIPMENT_DATE"] = "Свойство заказа (Дата отгрузки)";
$MESS[$suffix.".PROPERTY_OF_TRACK_NUMBER"] = "Свойство заказа (Трек-номер отправления)";
$MESS[$suffix.".SKU_PROPERTY_FOR_PRODUCTS"] = "Артикул в Wildberries (для товара, комплекта или набора)";
$MESS[$suffix.".SKU_PROPERTY_FOR_PRODUCT_OFFERS"] = "Артикул в Wildberries (для торгового предложения)";
$MESS[$suffix.".BARCODE_AS_OFFER_ID"] = "Использовать штрихкод как артикул на Wildberries";
$MESS[$suffix.".PACKAGING_RATIO_PRODUCT_PROPERTIES"] = "Коэффициент упаковки (для простых товаров и торговых предложений)";
$MESS[$suffix.".PACKAGING_RATIO_OFFERS_PROPERTIES"] = "Коэффициент упаковки (только для торговых предложений)";
$MESS[$suffix.".PACKAGING_RATIO_MESSAGE"] = "<p>Выберите свойство для настройки коэффициента упаковки, в котором храните количество единиц в упаковке.<br> Цена товара будет разделена на значение выбранного коэффициента, количество товара будет умножено.</p><p>По умолчанию коэффициент упаковки равен 1.</p>";
$MESS[$suffix.".USER_DESCRIPTION_TEMPLATE"] = "Шаблон комментария";
$MESS[$suffix.".USER_DESCRIPTION_TEMPLATE_NOTE"] =
    "<div style='text-align:left;'>"
        ."В шаблоне комментария доступны следующие метки:<br>"
        ."#SHIPMENT_ID# - ID отправления на Wildberries<br>"
        ."#CREATE_DATE# - дата заказа<br>"
    ."</div>"
;
$MESS[$suffix.".SITE_NOTE"] =
    "<div style='color:red;'><strong>Внимание!</strong> Проверьте и заполните обязательные настройки: <ul style='text-align:left;'><li>ID сайта</li><li>Тип плательщика</li><li>Служба доставки</li><li>Платежная система</li><li>Свойство заказа (Внешний номер заказа)</li><li>Свойство заказа (Дата отгрузки)</li><li>Артикул в Wildberries (для товара, комплекта или набора)</li><li>Артикул в Wildberries (для торгового предложения)</li></ul></div>"
;

$MESS[$suffix.".STATUS_HEAD"] = "Соответствие статусов c Wildberries";
$MESS[$suffix.".NOT_SELECTED"] = "Не выбрано";
$statusSuffix = "STATUS_";
$MESS[$suffix.".".$statusSuffix."WAITING"] = "Сборочное задание в работе";
$MESS[$suffix.".".$statusSuffix."SORTED"] = "Сборочное задание отсортировано";
$MESS[$suffix.".".$statusSuffix."SOLD"] = "Сборочное задание получено клиентом";
$MESS[$suffix.".".$statusSuffix."CANCELED"] = "Отмена сборочного задания";
$MESS[$suffix.".".$statusSuffix."CANCELED_BY_CLIENT"] = "Отмена сборочного задания клиентом";
$MESS[$suffix.".".$statusSuffix."DECLINED_BY_CLIENT"] = "Отмена сборочного задания клиентом в первый час";
$MESS[$suffix.".".$statusSuffix."DEFECT"] = "Отмена сборочного задания по причине брака";
$MESS[$suffix.".".$statusSuffix."READY_FOR_PICKUP"] = "Сборочное задание прибыло на ПВЗ";
$MESS[$suffix.".STATUS_NOTE"] =
    "<div style='color:red;'><strong>Внимание!</strong> В данный момент реализовано одностороннее получение статусов из Wildberries на сайт. Отправка статусов с сайта на Wildberries находится в разработке.</div>"
;

$MESS[$suffix.".FLAG_HEAD"] = "Установка флагов для статусов с Wildberries";
$MESS[$suffix.".PAYMENT_FLAG"] = "Флаг оплаты в заказе ставится, если на Wildberries статус";
$MESS[$suffix.".SHIPPING_FLAG"] = "Флаг отгрузки в заказе ставится, если на Wildberries статус";
$MESS[$suffix.".CANCELLED_FLAG"] = "Флаг отмены в заказе ставится, если на Wildberries статус";

$MESS[$suffix.".DISALLOW_STATUSES_HEAD"] = "Ограничения";
$MESS[$suffix.".DISALLOW_STATUSES"] = "Не обновлять заказы, если в Битрикс статус";

$MESS[$suffix.".STOCKS_AGENT_HEAD"] = "Обновление остатков";
$MESS[$suffix.".STOCKS_SCHEDULER_IS_ON"] = "Включить авто-обновление остатков";
$MESS[$suffix.".STOCKS_MINIMAL"] = "Минимальное разрешенное кол-во на остатке";
$MESS[$suffix.".STOCKS_MINIMAL_NOTE"] = "Если остаток на сайте меньше минимального разрешенного значения, то в Wildberries будет выгружен остаток 0";
$MESS[$suffix.".WAREHOUSE_ID"] = "ID склада на Wildberries";
$MESS[$suffix.".STOCKS_NOTE"] =
    "<div style='text-align:left;'>Если ID склада не указан, будет выбран первый доступный склад Wildberries<br>"
    ."<span style='color:red;'><strong>Внимание!</strong> Обновление остатков доступно только при идентификации товаров по штрихкодам</span></div>"
;

$MESS[$suffix.".PRICES_AGENT_HEAD"] = "Обновление цен";
$MESS[$suffix.".PRICES_SCHEDULER_IS_ON"] = "Включить авто-обновление цен";
$MESS[$suffix.".PRICE_TYPE"] = "Используемый тип цен";
$MESS[$suffix.".PRICE_NOTE"] = "<span style='color:red;'><strong>Внимание!</strong> Цены обновляются только если включено обновление остатков</span>";

$MESS[$suffix.".DELETE_HEAD"] = "Удаление аккаунта";
$MESS[$suffix.".DELETE_ACCOUNT"] = "Удалить аккаунт";

$MESS[$suffix.".ADD_ACCOUNT"] = "Добавить аккаунт";

$MESS[$suffix.".SERVICE_MSG"] = "<div class='wbs24_wbapi_service_msg'><img src='/bitrix/images/wbs24.wbapi/logo.png' alt='Логотип WEB Симфония' style='padding: 0 10px 0 0;'></div>";
