<?php
$suffix = strtoupper(basename(dirname(__DIR__, 3)));

$MESS[$suffix.".TAB_NAME"] = "Базовые настройки";

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

$MESS[$suffix.".DELETE_HEAD"] = "Удаление аккаунта";
$MESS[$suffix.".DELETE_ACCOUNT"] = "Удалить аккаунт";

$MESS[$suffix.".ADD_ACCOUNT"] = "Добавить аккаунт";

$MESS[$suffix.".SERVICE_MSG"] = "<div class='wbs24_wbapi_service_msg'><img src='/bitrix/images/wbs24.wbapi/logo.png' alt='Логотип WEB Симфония' style='padding: 0 10px 0 0;'></div>";
