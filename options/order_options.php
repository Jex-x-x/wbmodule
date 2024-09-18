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

// Получение профиля
$profileId = $_REQUEST['profile_id']; // NEED ACCOUNT_INDEX

// Option object work
$optionObject = new Wrappers\Option();
$account = $optionObject->setPrefix($profileId); // NEED ACCOUNT
$currentUserId = $optionObject->get($moduleId, 'userId');
$scheduler_interval = $optionObject->get($moduleId, 'scheduler_interval');
$isHttps = $optionObject->get($moduleId, 'isHttps');

// Settings object work
$settingsObject = new Settings();
$accounts = $settingsObject->getAccounts();
$settingsObject->loadJs([$profileId]);
$settingsObject->loadCss();
$allSites = $settingsObject->getSiteId();
$allOrderStatuses = $settingsObject->getAllOrderStatuses();
$allDeliveryService = $settingsObject->getDeliveryService();
$allPaymentSystem = $settingsObject->getPaymentSystem();
$isCurlInstalled = $settingsObject->isCurlInstalled(); // Проверка, уставновлен ли Curl
$settingsError = $settingsObject->getLastError();
if (isset($_REQUEST["clear-logs"])) { // Удаление всех логов
    $settingsObject->clearLogs();
}
$htmlLogsForDowload = $settingsObject->getHtmlLogsForDowload(); // Получение html с логами для скачивания
$allCustomers = $settingsObject->getCustomerIds($currentUserId);

$arAllOptions["main"] = [
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
];
$arAllOptions["main2"] = [
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
$arAllOptions["main3"] = [
    [$account."deliveryServiceId", Loc::getMessage($suffix.".DELIVERY_SERVICE_ID"), "nothing", ["selectbox", $allDeliveryService]],
    [$account."paymentSystemId", Loc::getMessage($suffix.".PAYMENT_SYSTEM_ID"), "nothing", ["selectbox", $allPaymentSystem]],
    //[$account."notUseAutoPay", Loc::getMessage($suffix.".NOT_USE_AUTO_PAY"), "", ["checkbox", ""]],
];
$arAllOptions["main4"] = [
    [$account."barcodeAsOfferId", Loc::getMessage($suffix.".BARCODE_AS_OFFER_ID"), "", ["checkbox", ""]],
    [$account."userDescriptionTemplate", Loc::getMessage($suffix.".USER_DESCRIPTION_TEMPLATE"), "", ["text", "70"]],
    ['note' => Loc::getMessage($suffix.".USER_DESCRIPTION_TEMPLATE_NOTE")],
    [
        $account."allowCanBuyZero",
        \ShowJSHint(Loc::getMessage($suffix.".ALLOW_CAN_BUY_ZERO_HINT"), ["return" => true])
        . Loc::getMessage($suffix.".ALLOW_CAN_BUY_ZERO"),
        "",
        ["checkbox", ""]
    ],

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
    $arAllOptions['main4'][] = [$account.$statusCode, Loc::getMessage($suffix.".".strtoupper($statusCode)), "nothing", ["selectbox", $allOrderStatuses]];
}

$arrForFlags = [];
$arrForFlags['nothing'] = Loc::getMessage($suffix.".NOT_SELECTED");
foreach ($statusesList as $statusCode) {
    $arrForFlags[$statusCode] = Loc::getMessage($suffix.".".strtoupper($statusCode));
}

$arAllOptions['main4'][] = Loc::getMessage($suffix.".FLAG_HEAD");
$arAllOptions['main4'][] = [$account."paymentFlag", Loc::getMessage($suffix.".PAYMENT_FLAG"), "nothing", ["multiselectbox", $arrForFlags]];
$arAllOptions['main4'][] = [$account."shippingFlag", Loc::getMessage($suffix.".SHIPPING_FLAG"), "nothing", ["multiselectbox", $arrForFlags]];
$arAllOptions['main4'][] = [$account."cancelledFlag", Loc::getMessage($suffix.".CANCELLED_FLAG"), "nothing", ["multiselectbox", $arrForFlags]];

$arAllOptions['main4'][] = Loc::getMessage($suffix.".DISALLOW_STATUSES_HEAD");
$arAllOptions['main4'][] = [$account."disallowStatuses", Loc::getMessage($suffix.".DISALLOW_STATUSES"), "nothing", ["multiselectbox", $allOrderStatuses]];

if ((isset($_REQUEST["save"]) || isset($_REQUEST["apply"]) || isset($_REQUEST["addAccount"])) && check_bitrix_sessid()) {
    __AdmSettingsSaveOptions($moduleId, $arAllOptions["main"]);
    __AdmSettingsSaveOptions($moduleId, $arAllOptions["main2"]);
    __AdmSettingsSaveOptions($moduleId, $arAllOptions["main3"]);
    __AdmSettingsSaveOptions($moduleId, $arAllOptions["main4"]);

    $personTypeId = $_REQUEST[$account."personTypeId"] ?? '';
    $optionObject->set($moduleId, 'personTypeId', $personTypeId);

    $propertyOfExternalOrderNumber = $_REQUEST[$account."propertyOfExternalOrderNumber"] ?? '';
    $optionObject->set($moduleId, 'propertyOfExternalOrderNumber', $propertyOfExternalOrderNumber);

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

    if ($isCurlInstalled) {
        $agents = new Agents();
        $agents->updateAgents($accounts);

        $agentsStocks = new Agents\Stocks();
        $agentsStocks->updateAgents($accounts);

        $agentsPrices = new Agents\Prices();
        $agentsPrices->updateAgents($accounts);
    }

    if (isset($_REQUEST["addAccount"])) $settingsObject->addAccount();
}

$aTabs[] = [
    "DIV" => str_replace(".", "_", $moduleId),
    "TAB" => Loc::getMessage($suffix.".TAB_NAME"),
    "ICON" => "settings",
    "TITLE" => '',
];

$tabControl = new CAdminTabControl("tabControl", $aTabs);
?>
<form method="post" action="<?=$redirect?>" name="<?=str_replace(".", "_", $moduleId)?>">
    <?
    echo bitrix_sessid_post();

    $tabControl->Begin();

    $tabControl->BeginNextTab();

    __AdmSettingsDrawList($moduleId, $arAllOptions["main"]);

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

    __AdmSettingsDrawList($moduleId, $arAllOptions["main2"]);

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

    __AdmSettingsDrawList($moduleId, $arAllOptions["main3"]);

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

    __AdmSettingsDrawList($moduleId, $arAllOptions["main4"]);

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
