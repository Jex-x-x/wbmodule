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
$settingsObject->loadJs([$profileId]);
$settingsObject->loadCss();

if ((isset($_REQUEST["save"]) || isset($_REQUEST["apply"]) || isset($_REQUEST["addAccount"])) && check_bitrix_sessid()) {
    if (isset($_REQUEST["addAccount"])) $settingsObject->addAccount();
    // для group_rights
    $REQUEST_METHOD = 'POST';
    $Update = 'Y';
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
