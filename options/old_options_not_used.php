<?php
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Wbs24\Wbapi\{
    Agents,
    Settings,
    Wrappers
};

if (!$USER->IsAdmin()) return;

$moduleId = basename(dirname(__DIR__, 1));
$module_id = $moduleId; // для group_rights
$suffix = strtoupper($moduleId);

Loc::loadMessages(__FILE__);
Loader::includeModule($moduleId);

$request = Application::getInstance()->getContext()->getRequest();
$uriString = $request->getRequestUri();
$uri = new Uri($uriString);
$redirect = $uri->getUri();

$settingsObject = new Settings();
$optionObject = new Wrappers\Option();

$accounts = $settingsObject->getAccounts();
$settingsObject->loadJs($accounts);
$settingsObject->loadCss();

// Получение данных для настройки подключаемых свойств к заказу
$allSites = $settingsObject->getSiteId();
$allOrderStatuses = $settingsObject->getAllOrderStatuses();
$allDeliveryService = $settingsObject->getDeliveryService();
$allPaymentSystem = $settingsObject->getPaymentSystem();
$allDays = $settingsObject->getDays();
$priceTypes = $settingsObject->getPriceTypes();

// Проверка, уставновлен ли Curl
$isCurlInstalled = $settingsObject->isCurlInstalled();
$settingsError = $settingsObject->getLastError();

// Удаление всех логов
if (isset($_REQUEST["clear-logs"])) {
    $settingsObject->clearLogs();
}
// Получение html с логами для скачивания
$htmlLogsForDowload = $settingsObject->getHtmlLogsForDowload();

$aTabs = [];
$arAllOptions = [];

foreach ($accounts as $i) {
    $account = $optionObject->setPrefix($i);
    $currentUserId = $optionObject->get($moduleId, 'userId');
    $allCustomers = $settingsObject->getCustomerIds($currentUserId);

    $aTabs[] = [
        "DIV" => str_replace(".", "_", $moduleId).'_a'.$i,
        "TAB" => Loc::getMessage($suffix.".SETTINGS").$i,
        "ICON" => "settings",
        "TITLE" => Loc::getMessage($suffix.".TITLE").$i,
    ];

    // Получение настроек связанных с API
    $apiKey = $optionObject->get($moduleId, 'apiKey');
    //$clientId = $optionObject->get($moduleId, 'clientId');

    // Получение настроек связанных с автозапуском
    $scheduler_interval = $optionObject->get($moduleId, 'scheduler_interval');
    $isHttps = $optionObject->get($moduleId, 'isHttps');

    $arAllOptions[$account."main"] = [
        (
            (!$isCurlInstalled)
            ? ['note' => Loc::getMessage($suffix.".CURL_NOTE")]
            : null
        ),
        (
            ($settingsError)
            ? ['note' => Loc::getMessage($suffix.".MODULES_ERROR")]
            : null
        ),
        Loc::getMessage($suffix.".MAIN_HEAD"),
        [
            '',
            '<div class="wbs24-api-key">' . Loc::getMessage($suffix.".API_KEY") . '</div>',
            '<input type="text" size="" maxlength="500" value="'. $apiKey .'" name="'. $account .'apiKey">',
            ['statichtml']
        ],
        //[$account."clientId", Loc::getMessage($suffix.".CLIENT_ID"), "", ["text", ""]],
        [$account."numberOfDaysToProcessOrders", Loc::getMessage($suffix.".NUMBER_OF_DAYS_TO_PROCESS_ORDERS"), "30", ["selectbox", $allDays]],
        [$account."testMode", Loc::getMessage($suffix.".TEST_MODE"), "", ["checkbox", ""]],
        [$account."saveApiLog", Loc::getMessage($suffix.".SAVE_API_LOG"), "", ["checkbox", ""]],
        (
            (!$apiKey)
            ? ['note' => Loc::getMessage($suffix.".API_NOTE")]
            : []
        ),
        [
            '',
            \ShowJSHint(Loc::getMessage($suffix.".LOG_HINT_1"), ["return" => true])
            . Loc::getMessage($suffix.".LOG_FOR_DOWNLOAD"),
            $htmlLogsForDowload ? $htmlLogsForDowload : Loc::getMessage($suffix.".LOG_FOR_NO_DOWNLOAD"),
            ['statichtml']
        ],
        [
            '',
            '<div class="wbs24-clear-logs-hint">'
            . \ShowJSHint(Loc::getMessage($suffix.".LOG_HINT_2"), ["return" => true])
            . '</div>', '<input type="submit" name="clear-logs" value="'.Loc::getMessage($suffix.".CLEAR_LOG_BUTTON_NAME").'">',
            ['statichtml']
        ],

        //Loc::getMessage($suffix.".RFBS_HEAD"),
        //[$account."rfbs", Loc::getMessage($suffix.".RFBS"), "", ["checkbox", ""]],
    ];
    $arAllOptions[$account."main2"] = [
        Loc::getMessage($suffix.".AGENT_HEAD"),
        [$account."scheduler_is_on", Loc::getMessage($suffix.".SCHEDULER_IS_ON"), "", ["checkbox", ""]],
        [$account."scheduler_interval", Loc::getMessage($suffix.".SCHEDULER_INTERVAL"), "10", ["text", ""]],
        [$account."isHttps", Loc::getMessage($suffix.".IS_HTTPS"), "nothing", ["selectbox", ['nothing' => Loc::getMessage($suffix.".NOT_SELECTED"), 'Y' => 'HTTPS', 'N' => 'HTTP']]],
        (
            ($isHttps == 'nothing' || empty($scheduler_interval))
            ? ['note' => Loc::getMessage($suffix.".AGENT_NOTE")]
            : []
        ),

        Loc::getMessage($suffix.".SITE_HEAD"),
        [$account."userId", Loc::getMessage($suffix.".USER_ID"), "", ["text", ""]],
        [$account."customerId", Loc::getMessage($suffix.".CUSTOMER_ID"), "0", ["selectbox", $allCustomers]],
        ['note' => Loc::getMessage($suffix.".USER_NOTE")],
        [$account."allowCombineOrders", Loc::getMessage($suffix.".ALLOW_COMBINE_ORDERS"), "", ["checkbox", ""]],
        [$account."siteId", Loc::getMessage($suffix.".SITE_ID"), "nothing", ["selectbox", $allSites]],
    ];
    $arAllOptions[$account."main3"] = [
        [$account."deliveryServiceId", Loc::getMessage($suffix.".DELIVERY_SERVICE_ID"), "nothing", ["selectbox", $allDeliveryService]],
        [$account."paymentSystemId", Loc::getMessage($suffix.".PAYMENT_SYSTEM_ID"), "nothing", ["selectbox", $allPaymentSystem]],
        //[$account."notUseAutoPay", Loc::getMessage($suffix.".NOT_USE_AUTO_PAY"), "", ["checkbox", ""]],
    ];
    $arAllOptions[$account."main4"] = [
        [$account."barcodeAsOfferId", Loc::getMessage($suffix.".BARCODE_AS_OFFER_ID"), "", ["checkbox", ""]],
        [$account."userDescriptionTemplate", Loc::getMessage($suffix.".USER_DESCRIPTION_TEMPLATE"), "", ["text", "70"]],
        ['note' => Loc::getMessage($suffix.".USER_DESCRIPTION_TEMPLATE_NOTE")],

        Loc::getMessage($suffix.".STATUS_HEAD"),
        // статусы добавляются в массив ниже
    ];

    // добавить сопоставление статусов в настройки
    $statusSuffix = "status_";
    $statusesList = [
        $statusSuffix."waiting", // сборочное задание в работе
        $statusSuffix."sorted", // сборочное задание отсортировано
        $statusSuffix."sold", // сборочное задание получено клиентом
        $statusSuffix."canceled", // отмена сборочного задания
        $statusSuffix."canceled_by_client", // отмена сборочного задания клиентом
        $statusSuffix."declined_by_client", // отмена сборочного заказа клиента в первый час
        $statusSuffix."defect", // отмена сборочного заказа по причине брака
        $statusSuffix."ready_for_pickup", // сборочное задание прибыло на ПВЗ
    ];

    foreach ($statusesList as $statusCode) {
        $arAllOptions[$account.'main4'][] = [$account.$statusCode, Loc::getMessage($suffix.".".strtoupper($statusCode)), "nothing", ["selectbox", $allOrderStatuses]];
    }

    $arrForFlags = [];
    $arrForFlags['nothing'] = Loc::getMessage($suffix.".NOT_SELECTED");
    foreach ($statusesList as $statusCode) {
        $arrForFlags[$statusCode] = Loc::getMessage($suffix.".".strtoupper($statusCode));
    }

    $arAllOptions[$account.'main4'][] = Loc::getMessage($suffix.".FLAG_HEAD");
    $arAllOptions[$account.'main4'][] = [$account."paymentFlag", Loc::getMessage($suffix.".PAYMENT_FLAG"), "nothing", ["multiselectbox", $arrForFlags]];
    $arAllOptions[$account.'main4'][] = [$account."shippingFlag", Loc::getMessage($suffix.".SHIPPING_FLAG"), "nothing", ["multiselectbox", $arrForFlags]];
    $arAllOptions[$account.'main4'][] = [$account."cancelledFlag", Loc::getMessage($suffix.".CANCELLED_FLAG"), "nothing", ["multiselectbox", $arrForFlags]];

    $arAllOptions[$account.'main4'][] = Loc::getMessage($suffix.".DISALLOW_STATUSES_HEAD");
    $arAllOptions[$account.'main4'][] = [$account."disallowStatuses", Loc::getMessage($suffix.".DISALLOW_STATUSES"), "nothing", ["multiselectbox", $allOrderStatuses]];

    $arAllOptions[$account."main4"][] = Loc::getMessage($suffix.".STOCKS_AGENT_HEAD");
    $arAllOptions[$account."main4"][] = [$account."stocks_scheduler_is_on", Loc::getMessage($suffix.".STOCKS_SCHEDULER_IS_ON"), "", ["checkbox", ""]];
    $arAllOptions[$account."main4"][] = [$account."stocks_scheduler_interval", Loc::getMessage($suffix.".SCHEDULER_INTERVAL"), "60", ["text", ""]];
    $arAllOptions[$account."main4"][] = [$account."stocks_minimal", Loc::getMessage($suffix.".STOCKS_MINIMAL"), "1", ["text", ""]];
    $arAllOptions[$account."main4"][] = ['note' => Loc::getMessage($suffix.".STOCKS_MINIMAL_NOTE")];
    $arAllOptions[$account."main4"][] = [$account."warehouse_id", Loc::getMessage($suffix.".WAREHOUSE_ID"), "", ["text", ""]];
    $arAllOptions[$account."main4"][] = ['note' => Loc::getMessage($suffix.".STOCKS_NOTE")];

    $arAllOptions[$account."main4"][] = Loc::getMessage($suffix.".PRICES_AGENT_HEAD");
    $arAllOptions[$account."main4"][] = [$account."prices_scheduler_is_on", Loc::getMessage($suffix.".PRICES_SCHEDULER_IS_ON"), "", ["checkbox", ""]];
    $arAllOptions[$account."main4"][] = [$account."prices_scheduler_interval", Loc::getMessage($suffix.".SCHEDULER_INTERVAL"), "60", ["text", ""]];
    $arAllOptions[$account."main4"][] = [$account."price_type", Loc::getMessage($suffix.".PRICE_TYPE"), "nothing", ["selectbox", $priceTypes]];
    $arAllOptions[$account."main4"][] = ['note' => Loc::getMessage($suffix.".PRICE_NOTE")];

    if (count($accounts) > 1) {
        $arAllOptions[$account.'main4'][] = Loc::getMessage($suffix.".DELETE_HEAD");
    }
}

$aTabs[] = [
    "DIV" => str_replace(".", "_", $moduleId).'_rights',
    "TAB" => Loc::getMessage($suffix.".RIGHTS"),
    "ICON" => "settings",
    "TITLE" => Loc::getMessage($suffix.".RIGHTS_TITLE"),
];

if ((isset($_REQUEST["save"]) || isset($_REQUEST["apply"]) || isset($_REQUEST["addAccount"])) && check_bitrix_sessid()) {
    foreach ($accounts as $i) {
        $account = $optionObject->setPrefix($i);

        __AdmSettingsSaveOptions($moduleId, $arAllOptions[$account."main"]);
        __AdmSettingsSaveOptions($moduleId, $arAllOptions[$account."main2"]);
        __AdmSettingsSaveOptions($moduleId, $arAllOptions[$account."main3"]);
        __AdmSettingsSaveOptions($moduleId, $arAllOptions[$account."main4"]);

        $apiKey = $_REQUEST[$account."apiKey"] ?? '';
        $optionObject->set($moduleId, 'apiKey', $apiKey);

        $personTypeId = $_REQUEST[$account."personTypeId"] ?? '';
        $optionObject->set($moduleId, 'personTypeId', $personTypeId);

        $propertyOfExternalOrderNumber = $_REQUEST[$account."propertyOfExternalOrderNumber"] ?? '';
        $optionObject->set($moduleId, 'propertyOfExternalOrderNumber', $propertyOfExternalOrderNumber);

        /* $propertyOfShipmentDate = $_REQUEST[$account."propertyOfShipmentDate"] ?? '';
        $optionObject->set($moduleId, 'propertyOfShipmentDate', $propertyOfShipmentDate); */

        $propertyOfTrackNumber = $_REQUEST[$account."propertyOfTrackNumber"] ?? '';
        $optionObject->set($moduleId, 'propertyOfTrackNumber', $propertyOfTrackNumber);

        // сохранение идентификатора/свойства для offer id
        $offerId = $_REQUEST[$account."skuPropertyForProducts"] ?? '';
        $optionObject->set($moduleId, 'skuPropertyForProducts', $offerId);

        $tradeOfferId = $_REQUEST[$account."skuPropertyForProductOffers"] ?? '';
        $optionObject->set($moduleId, 'skuPropertyForProductOffers', $tradeOfferId);

        $packageProductRatio = $_REQUEST[$account."packageProductRatio"] ?? '';
        $optionObject->set($moduleId, 'packageProductRatio', $packageProductRatio);

        $packageOfferRatio = $_REQUEST[$account."packageOfferRatio"] ?? '';
        $optionObject->set($moduleId, 'packageOfferRatio', $packageOfferRatio);

        if (isset($_REQUEST[$account."deleteAccount"])) $settingsObject->deleteAccount($i);
    }

    if ($isCurlInstalled) {
        $agents = new Agents();
        $agents->updateAgents($accounts);

        $agentsStocks = new Agents\Stocks();
        $agentsStocks->updateAgents($accounts);

        $agentsPrices = new Agents\Prices();
        $agentsPrices->updateAgents($accounts);
    }

    if (isset($_REQUEST["addAccount"])) $settingsObject->addAccount();

    // для group_rights
    $REQUEST_METHOD = 'POST';
    $Update = 'Y';
}

$tabControl = new CAdminTabControl("tabControl", $aTabs);
?>
<form method="post" action="<?=$redirect?>" name="<?=str_replace(".", "_", $moduleId)?>">
    <?
    echo bitrix_sessid_post();

    $tabControl->Begin();

    foreach ($accounts as $i) {
        $account = $optionObject->setPrefix($i);

        $tabControl->BeginNextTab();
        ?>
        <tr>
            <td colspan="2">
                <div style="background:#615EFF;padding:10px;color:white;font-weight:700;text-align:center;border-radius:3px;">
                    <?=Loc::getMessage($suffix.".NEW_SETTINGS_VIEW")?>
                    <a href="/bitrix/admin/wbs24_wbapi_profile_main.php?profile_id=<?=$i?>&mid=wbs24.wbapi&options_type=base">
                        <span style="color:white;text-decoration:underline;">
                            <?=Loc::getMessage($suffix.".NEW_SETTINGS_BTN")?>
                        </span>
                    </a>
                </div>
            </td>
        </tr>
        <?
        __AdmSettingsDrawList($moduleId, $arAllOptions[$account."main"]);

        $siteId = $optionObject->get($moduleId, 'siteId');
        $personTypeId = $optionObject->get($moduleId, 'personTypeId');
        $deliveryServiceId = $optionObject->get($moduleId, 'deliveryServiceId');
        $paymentSystemId = $optionObject->get($moduleId, 'paymentSystemId');
        $propertyOfExternalOrderNumber = $optionObject->get($moduleId, 'propertyOfExternalOrderNumber');
        //$propertyOfShipmentDate = $optionObject->get($moduleId, 'propertyOfShipmentDate');
        $propertyOfTrackNumber = $optionObject->get($moduleId, 'propertyOfTrackNumber');
        $offerId = $optionObject->get($moduleId, 'skuPropertyForProducts');
        $tradeOfferId = $optionObject->get($moduleId, 'skuPropertyForProductOffers');
        $packageProductRatio = $optionObject->get($moduleId, 'packageProductRatio');
        $packageOfferRatio = $optionObject->get($moduleId, 'packageOfferRatio');

        $showSiteNote = (
            $siteId == 'nothing'
            || $personTypeId == 'nothing'
            || $deliveryServiceId == 'nothing'
            || $paymentSystemId == 'nothing'
            || $propertyOfExternalOrderNumber == 'nothing'
            //|| $propertyOfShipmentDate == 'nothing'
        );

        ?>
        <tr class="wbs24_wbapi_option_<?=$account?>track_number" style="display: none;">
            <td width="50%" class="adm-detail-content-cell-l">
                <?=Loc::getMessage($suffix.".PROPERTY_OF_TRACK_NUMBER");?>
                <a name="opt_propertyOfTrackNumber"></a>
            </td>
            <td width="50%" class="adm-detail-content-cell-r">
                <?=$settingsObject->getSelectForOrderProperties($personTypeId, $account.'propertyOfTrackNumber', $propertyOfTrackNumber);?>
            </td>
        </tr>
        <?

        __AdmSettingsDrawList($moduleId, $arAllOptions[$account."main2"]);

        // настройка типа плательщика
        ?>
        <tr>
            <td width="50%" class="adm-detail-content-cell-l">
                <?=Loc::getMessage($suffix.".PERSON_TYPE_ID");?>
                <a name="opt_personTypeId"></a>
            </td>
            <td width="50%" class="adm-detail-content-cell-r">
                <?=$settingsObject->getSelectForPayer($siteId, $account.'personTypeId', $personTypeId);?>
            </td>
        </tr>
        <?

        __AdmSettingsDrawList($moduleId, $arAllOptions[$account."main3"]);

        // настройка свойств заказа
        ?>
        <tr>
            <td width="50%" class="adm-detail-content-cell-l">
                <?=Loc::getMessage($suffix.".PROPERTY_OF_EXTERNAL_ORDER_NUMBER");?>
                <a name="opt_propertyOfExternalOrderNumber"></a>
            </td>
            <td width="50%" class="adm-detail-content-cell-r">
                <?=$settingsObject->getSelectForOrderProperties($personTypeId, $account.'propertyOfExternalOrderNumber', $propertyOfExternalOrderNumber);?>
            </td>
        </tr>
        <? /*
        <tr>
            <td width="50%" class="adm-detail-content-cell-l">
                <?=Loc::getMessage($suffix.".PROPERTY_OF_SHIPMENT_DATE");?>
                <a name="opt_propertyOfShipmentDate"></a>
            </td>
            <td width="50%" class="adm-detail-content-cell-r">
                <?=$settingsObject->getSelectForOrderProperties($personTypeId, $account.'propertyOfShipmentDate', $propertyOfShipmentDate);?>
            </td>
        </tr> */ ?>
        <?

        // настройка offer id
        ?>
        <tr>
            <td width="50%" class="adm-detail-content-cell-l">
                <?=Loc::getMessage($suffix.".SKU_PROPERTY_FOR_PRODUCTS");?>
                <a name="opt_skuPropertyForProducts"></a>
            </td>
            <td width="50%" class="adm-detail-content-cell-r">
                <?=$settingsObject->getSelectForOfferId($siteId, $account.'skuPropertyForProducts', $offerId);?>
            </td>
        </tr>
        <tr>
            <td width="50%" class="adm-detail-content-cell-l">
                <?=Loc::getMessage($suffix.".SKU_PROPERTY_FOR_PRODUCT_OFFERS");?>
                <a name="opt_skuPropertyForProductOffers"></a>
            </td>
            <td width="50%" class="adm-detail-content-cell-r">
                <?=$settingsObject->getSelectForOfferId($siteId, $account.'skuPropertyForProductOffers', $tradeOfferId);?>
            </td>
        </tr>
        <?

        // настройка package ratio
        ?>
        <tr>
            <td width="50%" class="adm-detail-content-cell-l">
                <?=Loc::getMessage($suffix.".PACKAGING_RATIO_PRODUCT_PROPERTIES");?>
                <a name="opt_packageProductRatio"></a>
            </td>
            <td width="50%" class="adm-detail-content-cell-r">
                <?=$settingsObject->getSelectForPackageRatio($siteId, $account.'packageProductRatio', $packageProductRatio);?>
            </td>
        </tr>
        <tr>
            <td width="50%" class="adm-detail-content-cell-l">
                <?=Loc::getMessage($suffix.".PACKAGING_RATIO_OFFERS_PROPERTIES");?>
                <a name="opt_packageOfferRatio"></a>
            </td>
            <td width="50%" class="adm-detail-content-cell-r">
                <?=$settingsObject->getSelectForPackageRatio($siteId, $account.'packageOfferRatio', $packageOfferRatio);?>
            </td>
        </tr>
        <?

        // сообщение об обязательных настройках
        ?>
        <tr class="wbs24_wbapi_option_<?=$account?>site_note" style="<?=$showSiteNote ? '' : 'display: none;'?>">
            <td colspan="2" align="center">
                <div class="adm-info-message-wrap" align="center">
                    <div class="adm-info-message"><?=Loc::getMessage($suffix.".SITE_NOTE");?></div>
                </div>
            </td>
        </tr>
        <?

        __AdmSettingsDrawList($moduleId, $arAllOptions[$account."main4"]);

        if (count($accounts) > 1) {
            ?>
            <tr>
                <td width="50%" class="adm-detail-content-cell-l">
                    <?=Loc::getMessage($suffix.".DELETE_ACCOUNT");?>
                </td>
                <td width="50%" class="adm-detail-content-cell-r">
                    <input type="checkbox" name="<?=$account?>deleteAccount" value="Y">
                </td>
            </tr>
            <?
        }
    }

    $tabControl->BeginNextTab();

    require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");

    $tabControl->Buttons([]);
    ?>
    <input type="submit" name="addAccount" class="wbs24_wbapi_option_add_account adm-btn-save" value="<?=Loc::getMessage($suffix.".ADD_ACCOUNT");?>">
    <?

    echo Loc::getMessage($suffix.".SERVICE_MSG");

    $tabControl->End();
    ?>
</form>

<?
if ((isset($_REQUEST["save"]) || isset($_REQUEST["apply"]) || isset($_REQUEST["addAccount"])) && check_bitrix_sessid()) {
    // вынесено в конец для сохранения group_rights
    LocalRedirect($redirect);
}
?>
