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

// Получение профиля
$profileId = $_REQUEST['profile_id'];

// Option object work
$optionObject = new Wrappers\Option();
$account = $optionObject->setPrefix($profileId);
$apiKey = $optionObject->get($moduleId, 'apiKey');

// Settings object work
$settingsObject = new Settings();
$settingsObject->loadJs([$profileId]);
$settingsObject->loadCss();
$allDays = $settingsObject->getDays();
$settingsError = $settingsObject->getLastError();  // Проверка ошибок
$accounts = $settingsObject->getAccounts();
if (isset($_REQUEST["clear-logs"])) { // Удаление всех логов
    $settingsObject->clearLogs();
}
$htmlLogsForDowload = $settingsObject->getHtmlLogsForDowload(); // Получение html с логами для скачивания

$arAllOptions["main"] = [
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
    [
        $account."numberOfDaysToProcessOrders",
        Loc::getMessage($suffix.".NUMBER_OF_DAYS_TO_PROCESS_ORDERS"),
        "30",
        ["selectbox", $allDays]
    ],
    [
        $account."testMode",
        Loc::getMessage($suffix.".TEST_MODE"),
        "",
        ["checkbox", ""]
    ],
    [
        $account."saveApiLog",
        Loc::getMessage($suffix.".SAVE_API_LOG"),
        "",
        ["checkbox", ""]
    ],
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
];

if (count($accounts) > 1) {
    $arAllOptions['main'][] = Loc::getMessage($suffix.".DELETE_HEAD");
    $arAllOptions['main'][] = [
        '',
        Loc::getMessage($suffix.".DELETE_ACCOUNT"),
        '<input type="checkbox" name="'.$account.'deleteAccount" value="Y">',
        ['statichtml']
    ];
}

if ((isset($_REQUEST["save"]) || isset($_REQUEST["apply"]) || isset($_REQUEST["addAccount"])) && check_bitrix_sessid()) {
    __AdmSettingsSaveOptions($moduleId, $arAllOptions["main"]);
    if (isset($_REQUEST["addAccount"])) $settingsObject->addAccount();
    if (isset($_REQUEST[$account."deleteAccount"])) {
        uasort($accounts, function ($a, $b) {
            if ($a == $b) {
                return 0;
            }
            return ($a < $b) ? -1 : 1;
        });
        foreach ($accounts as $key => $needAccount) {
            if ($needAccount == $profileId) {
                if (!empty($accounts[$key-1])) {
                    $redirect =
                    '/bitrix/admin/wbs24_wbapi_profile_main.php?profile_id='
                    .$accounts[$key-1]
                    .'&mid=wbs24.wbapi&options_type=base';
                } else {
                    $redirect =
                    '/bitrix/admin/wbs24_wbapi_profile_main.php?profile_id='
                    .$accounts[$key+1]
                    .'&mid=wbs24.wbapi&options_type=base';
                }
            }
        }

        $settingsObject->deleteAccount($profileId);
    }

    $apiKey = $_REQUEST[$account."apiKey"] ?? '';
    $optionObject->set($moduleId, 'apiKey', $apiKey);

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

