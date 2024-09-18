<?php
$suffix = strtoupper(basename(dirname(__DIR__, 3)));

$MESS[$suffix.".COLLECT_ORDER_ON_MP"] = "Обработка заказа на Wildberries";
$MESS[$suffix.".TITLE"] = "Обработка заказа на Wildberries";
$MESS[$suffix.".ORDERS_TAB_TEXT"] = "Данная вкладка предназначена для обработки заказа на Wildberries. Смена статуса заказа пройдет автоматически.";

$MESS[$suffix.".ORDERS_TAB_NOTE"] = "В данный момент модуль не может управлять заказами на Wildberries (управление доступно только из <a href='https://seller.wildberries.ru/' target='_blank'>ЛК Wildberries</a>).";

/* $MESS[$suffix.".NOT_SELECTED"] = "Не выбрано";
$MESS[$suffix.".DESCRIPTION"] = "1) Если вы НЕ хотите разбивать свой заказ на отправления, то отметьте галочкой настройку Добавить все товары в одну упаковку <br>2) Если же вы собираетесь разбить свой заказ на несколько отправлений, переходите к настройки 'Введите количество упаковок' и выберите количество отправлений, на которые будут разбиты ваши товары в заказе";
$MESS[$suffix.".ONE_PACKAGE"] = "Добавить все товары в одну упаковку";
$MESS[$suffix.".PACKAGES"] = "Введите количество упаковок:";
$MESS[$suffix.".PACKAGE_NUMBER"] = "Упаковка-№";
$MESS[$suffix.".SELECT_PACKAGE_FOR_PRODUCT"] = "Выберите упаковку для товара"; */

$MESS[$suffix.".COLLECT_ORDER"] = "Собрать заказ";
$MESS[$suffix.".ORDER_SUCCESSFULLY_COLLECTED"] = "Заказ успешно собран на Wildberries";
$MESS[$suffix.".COLLECT_ORDER_NOTE"] = "Если вы проверили наличие товара и готовы к отгрузке, нажмите кнопку \"Собрать заказ\".";

$MESS[$suffix.".CREATE_ACT"] = "Создать акт и накладную";
$MESS[$suffix.".ACT_ERROR"] = "В данный момент документы не доступны";
$MESS[$suffix.".ACT_STATUS_IN_PROCESS"] = "Акт и накладная формируется";
$MESS[$suffix.".OPEN_ACT_PAGE"] = "Открыть страницу с актом и накладной";
$MESS[$suffix.".ORDER_SHIPMENT_DATE"] = "Дата отгрузки";
$MESS[$suffix.".CREATE_ACT_NOTE"] =
    "Wildberries предоставляет акт и накладную только в день отгрузки.<br>
    Wildberries формирует акт и накладную для всех заказов на день отгрузки."
;

$MESS[$suffix.".PACKAGE_LABEL"] = "Напечатать маркировку";
$MESS[$suffix.".PACKAGE_LABEL_IN_PROCESS"] = "Маркировка формируется";
$MESS[$suffix.".OPEN_LABEL_PAGE"] = "Открыть страницу с маркировкой";
$MESS[$suffix.".PACKAGE_LABEL_NOTE"] = "Маркировка формируется для каждого заказа отдельно.";

$MESS[$suffix.".ORDER_CANCEL"] = "Отменить заказ";
$MESS[$suffix.".ORDER_CANCEL_CONFIRM"] = "Вы уверены, что хотите отменить заказ? Отмена безвозвратна и негативно отразится на рейтинге вашего магазина!";
$MESS[$suffix.".ORDER_CANCEL_ERROR_LABEL"] = "Ошибка отмены заказа";
$MESS[$suffix.".ORDER_CANCEL_LABEL"] = "Заказ отменен";
$MESS[$suffix.".ORDER_CANCEL_NOTE"] = "Отмена безвозвратна и негативно отразится на рейтинге вашего магазина!";

$MESS[$suffix.".ORDER_ERROR_POSTING_NOT_FOUND"] = "Заказа нет в личном кабинете партнёра";
$MESS[$suffix.".ORDER_ERROR_POSTING_ALREADY_CANCELLED"] = "Заказ уже отменён";
$MESS[$suffix.".ORDER_ERROR_POSTING_ALREADY_SHIPPED"] = "Заказ уже собран";
$MESS[$suffix.".ORDER_ERROR_HAS_INCORRECT_STATUS"] = "У заказа некорректный статус";
$MESS[$suffix.".ORDER_ERROR_HAS_INCORRECT_PRODUCT_QUANTITY"] = "Неправильное количество продуктов или неправильный SKU в запросе";
$MESS[$suffix.".ORDER_ERROR_UNKNOWN"] = "Неизвестная ошибка";

$MESS[$suffix.".ORDERS_TAB_BOTTOM_TEXT"] =
    "Обработка и изменение статусов заказов проходит в этой вкладке. Статусы заказов из вкладки \"Заказ\" обновляются автоматически.<br>
    <br>
    В данный момент доступно:<br>
    1) Сборка в одну упаковку;<br>
    2) Формирование и печать акта и накладной, а так же маркировки;<br>
    3) Отмена заказа.<br>
    <br>
    В данной вкладке недоступно:<br>
    1) Разбивка заказа на несколько упаковок;<br>
    2) Частичная отмена заказа;<br>
    3) Арбитраж и другие специфические операции.<br>
    Данные процедуры можно сделать из личного кабинета Wildberries.<br>
    <br>
    Мы продолжаем работать над улучшением модуля и расширением его функционала."
;
