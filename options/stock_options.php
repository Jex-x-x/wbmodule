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
$suffix = strtoupper($moduleId);

Loc::loadMessages(__FILE__);
Loader::includeModule($moduleId);

$request = Application::getInstance()->getContext()->getRequest();
$uriString = $request->getRequestUri();
$uri = new Uri($uriString);
$redirect = $uri->getUri();

$profileId = $_REQUEST['profile_id']; // NEED ACCOUNT_INDEX

$optionObject = new Wrappers\Option();
$account = $optionObject->setPrefix($profileId); // NEED ACCOUNT

$settingsObject = new Settings();
$settingsObject->loadJs([$profileId]);
$settingsObject->loadCss();
$accounts = $settingsObject->getAccounts();
$accountsList = $settingsObject->getAccountsForSelect($profileId);
$isCurlInstalled = $settingsObject->isCurlInstalled();
$settingsError = $settingsObject->getLastError();
$warehouses = $settingsObject->getWarehouses();

// Получение значений свойств для остатков
$productStockPropertyValue = $optionObject->get($moduleId, 'productStockProperty');
$offerStockPropertyValue = $optionObject->get($moduleId, 'offerStockProperty');
$siteId = $optionObject->get($moduleId, 'siteId');

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
    Loc::getMessage($suffix.".STOCKS_AGENT_HEAD"),
    [
        $account."stocks_scheduler_is_on",
        Loc::getMessage($suffix.".STOCKS_SCHEDULER_IS_ON"),
        "",
        ["checkbox", ""]
    ],
    [
        'note' => Loc::getMessage($suffix.".SKU_NOTE")
    ],
    [
        $account."stocks_scheduler_interval",
        Loc::getMessage($suffix.".SCHEDULER_INTERVAL"),
        "60",
        ["text", ""]
    ],
    [
        $account."stockType",
        \ShowJSHint(Loc::getMessage($suffix.".SELECT_STOCK_TYPE_HINT"), ["return" => true])
        . Loc::getMessage($suffix.".SELECT_STOCK_TYPE"),
        "catalog_quantity",
        [
            "selectbox",
            $warehouses
        ]
    ],
    [
        '',
        '<div class="'.$account.'wbs24-wbapi-stock-properties">' . Loc::getMessage($suffix.".PRODUCT_STOCK_PROPERTY") . '</div>',
        $settingsObject->getSelectForStockProperty(
            $siteId,
            $account.'productStockProperty',
            $productStockPropertyValue
        ),
        ['statichtml']
    ],
    [
        '',
        '<div class="'.$account.'wbs24-wbapi-stock-properties">' . Loc::getMessage($suffix.".OFFER_STOCK_PROPERTY") . '</div>',
        $settingsObject->getSelectForStockProperty(
            $siteId,
            $account.'offerStockProperty',
            $offerStockPropertyValue
        ),
        ['statichtml']
    ],
    [
        $account."stocks_minimal",
        Loc::getMessage($suffix.".STOCKS_MINIMAL"),
        "1",
        ["text", ""]
    ],
    [
        'note' => Loc::getMessage($suffix.".STOCKS_MINIMAL_NOTE")
    ],
    [
        $account."resetStock",
        \ShowJSHint(
            Loc::getMessage($suffix.".STOCKS_HINT"),
            ["return" => true]
        ) . Loc::getMessage($suffix.".RESET_STOCK"),
        "",
        ["checkbox", ""]
    ],
    [
        $account."warehouse_id",
        Loc::getMessage($suffix.".WAREHOUSE_ID"),
        "",
        ["text", ""]
    ],
    [
        $account."linked_accounts",
        \ShowJSHint(
            Loc::getMessage($suffix.".LINKED_ACCOUNTS_HINT"),
            ["return" => true]
        ) . Loc::getMessage($suffix.".LINKED_ACCOUNTS"),
        "",
        ["multiselectbox", $accountsList]
    ],
    [
        'note' => Loc::getMessage($suffix.".STOCKS_NOTE")
    ],
];

if (
    !$siteId
    || $siteId == 'nothing'
) {
    $arAllOptions["main"] = [
        [
            'note' => Loc::getMessage($suffix.".WARNING_NOTE")
        ]
    ];
}


if ((isset($_REQUEST["save"]) || isset($_REQUEST["apply"]) || isset($_REQUEST["addAccount"])) && check_bitrix_sessid()) {
    __AdmSettingsSaveOptions($moduleId, $arAllOptions["main"]);
    if (isset($_REQUEST["addAccount"])) $settingsObject->addAccount();

    $productStockProperty = $_REQUEST[$account."productStockProperty"] ?? '';
    $optionObject->set($moduleId, 'productStockProperty', $productStockProperty);

    $offerStockProperty = $_REQUEST[$account."offerStockProperty"] ?? '';
    $optionObject->set($moduleId, 'offerStockProperty', $offerStockProperty);

    if ($isCurlInstalled) {
        $agents = new Agents();
        $agents->updateAgents($accounts);

        $agentsStocks = new Agents\Stocks();
        $agentsStocks->updateAgents($accounts);

        $agentsPrices = new Agents\Prices();
        $agentsPrices->updateAgents($accounts);
    }

    LocalRedirect($redirect);
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

    $tabControl->Buttons([]);
    ?>
    <input type="submit" name="addAccount" class="wbs24_wbapi_option_add_account adm-btn-save" value="<?=Loc::getMessage($suffix.".ADD_ACCOUNT");?>">
    <?
    echo Loc::getMessage($suffix.".SERVICE_MSG");
    $tabControl->End();
    ?>
</form>

