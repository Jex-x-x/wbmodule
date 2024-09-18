<?
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Wbs24\Wbapi\{
    Settings,
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

$Settings = new Settings();
$accounts = $Settings->getAccounts();

$aTabs = [
	[
		"DIV" => str_replace(".", "_", $moduleId),
		"TAB" => Loc::getMessage($suffix.".SETTINGS"),
		"ICON" => "settings",
		"TITLE" => Loc::getMessage($suffix.".TITLE"),
	],
];
$arAllOptions = [
	"main" => [
		['note' => Loc::getMessage($suffix.".NOTE")],
	],
];

if ((isset($_REQUEST["save"]) || isset($_REQUEST["apply"])) && check_bitrix_sessid()) {
	__AdmSettingsSaveOptions($moduleId, $arAllOptions["main"]);
	LocalRedirect($redirect);
}

$tabControl = new CAdminTabControl("tabControl", $aTabs);
?>
<form method="post" action="<?=$redirect?>" name="<?=str_replace(".", "_", $moduleId)?>">
	<?
	echo bitrix_sessid_post();

	$tabControl->Begin();

	$tabControl->BeginNextTab();
    ?>
        <tr>
            <td colspan="2">
                <div style="background:#615EFF;padding:10px;color:white;font-weight:700;text-align:center;border-radius:3px;">
                    <?=Loc::getMessage($suffix.".NEW_SETTINGS_VIEW")?>
                    <a href="/bitrix/admin/wbs24_wbapi_profile_main.php?profile_id=<?=$accounts[0]?>&mid=wbs24.wbapi&options_type=base">
                        <span style="color:white;text-decoration:underline;">
                            <?=Loc::getMessage($suffix.".NEW_SETTINGS_BTN")?>
                        </span>
                    </a>
                </div>
            </td>
        </tr>
    <?

	//$tabControl->Buttons([]); // отключено, т.к. пока нет свойств
	$tabControl->End();
	?>
</form>
