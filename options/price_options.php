<?php
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Wbs24\Wbapi\{
    Agents,
    Settings,
    Wrappers,
    Formula
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

$formulaObject = new Formula();

$settingsObject = new Settings();
$settingsObject->loadJs([$profileId]);
$settingsObject->loadCss();
$accounts = $settingsObject->getAccounts();
$isCurlInstalled = $settingsObject->isCurlInstalled();
$settingsError = $settingsObject->getLastError();
$priceMarks = $settingsObject->getPriceTypes(true);
$siteId = $optionObject->get($moduleId, 'siteId');
$siteIdPoductProperties = $settingsObject->getSimpleProductProperties($siteId);
$siteIdOfferProperties = $settingsObject->getOfferProductProperties($siteId);

// Получение меток цены
$productPrice = $optionObject->get($moduleId, 'productPrice');
$offerPrice = $optionObject->get($moduleId, 'offerPrice');

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
    Loc::getMessage($suffix.".PRICES_AGENT_HEAD"),
    [
        $account."prices_scheduler_is_on",
        Loc::getMessage($suffix.".PRICES_SCHEDULER_IS_ON"),
        "",
        ["checkbox", ""]
    ],
    [
        'note' => Loc::getMessage($suffix.".SKU_NOTE")
    ],
    [
        $account."prices_scheduler_interval",
        Loc::getMessage($suffix.".SCHEDULER_INTERVAL"),
        "60",
        ["text", ""]
    ],
    [
        '',
        \ShowJSHint(
            Loc::getMessage($suffix.".PRICE_HINT"),
            ["return" => true]
        ) . Loc::getMessage($suffix.".PRICE"),
        '',
        ['statichtml']
    ],
    [
        '',
        Loc::getMessage($suffix.".PRODUCTS"),
        $settingsObject->getNameInput(
            $account."productPrice",
            [
                'PRODUCTS' => $siteIdPoductProperties,
            ],
            $productPrice,
            $account
        ),
        ['statichtml']
    ],
    [
        '',
        Loc::getMessage($suffix.".OFFERS"),
        $settingsObject->getNameInput(
            $account."offerPrice",
            [
                'PRODUCTS' => $siteIdPoductProperties,
                'OFFERS' => $siteIdOfferProperties,
            ],
            $offerPrice,
            $account
        ),
        ['statichtml']
    ],
    [
        $account."discount",
        \ShowJSHint(
            Loc::getMessage($suffix.".DISCOUNT_HINT"),
            ["return" => true]
        ) . Loc::getMessage($suffix.".DISCOUNT"),
        "",
        ["text", ""]
    ],
    [
        'note' => Loc::getMessage($suffix.".PRICE_NOTE")
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
    // Установить допустимые метки
    $formulaObject->setMarks(array_merge(
        array_keys($priceMarks),
        array_keys($siteIdPoductProperties),
        array_keys($siteIdOfferProperties),
    ));
    $prices = [
        'productPrice',
        'offerPrice',
    ];
    foreach ($prices as $priceType) {
        $priceValue = $_REQUEST[$account.$priceType] ?? '';
        $optionObject->set(
            $moduleId,
            $priceType,
            $formulaObject->cleanFormula($priceValue)
        );
    }

    __AdmSettingsSaveOptions($moduleId, $arAllOptions["main"]);
    if (isset($_REQUEST["addAccount"])) $settingsObject->addAccount();

    if ($isCurlInstalled) {
        $agents = new Agents();
        $agents->updateAgents($accounts);

        $agentsStocks = new Agents\Stocks();
        $agentsStocks->updateAgents($accounts);

        $agentsPrices = new Agents\Prices();
        $agentsPrices->updateAgents($accounts);
    }

    // Принудительное обновление
    $optionObject->set(
        $moduleId,
        'forceUpdate',
        'Y'
    );

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

