<?php
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\EventManager;

Loc::loadMessages(__FILE__);

class wbs24_wbapi extends CModule {
    public $MODULE_ID = 'wbs24.wbapi';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $MODULE_CSS;
    public $MODULE_GROUP_RIGHTS = "Y";

    public function __construct() {
        $arModuleVersion = [];

        $path = str_replace("\\", "/", __FILE__);
        $path = substr($path, 0, strlen($path) - strlen("/index.php"));
        include($path."/version.php");

        if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        } else {
            $this->MODULE_VERSION = "0.1.0";
            $this->MODULE_VERSION_DATE = "2022-02-18";
        }

        $this->MODULE_NAME = Loc::getMessage("WBS24.WBAPI.INSTALL_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("WBS24.WBAPI.INSTALL_DESCRIPTION");
        $this->PARTNER_NAME = Loc::getMessage("WBS24.WBAPI.INSTALL_COMPANY_NAME");
        $this->PARTNER_URI  = "https://wbs24.ru/";
    }

    // Install functions
    public function InstallDB() {
        RegisterModule($this->MODULE_ID);

        global $DB;
        $arrErrors = false;
        $arrErrors = $DB->RunSqlBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/db/install.sql");
        if ($arrErrors) return $arrErrors;

        return true;
    }

    public function InstallFiles() {
        CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/tools/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/tools/".$this->MODULE_ID, true, true);
        CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/js/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js/".$this->MODULE_ID, true, true);
        CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/css/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/css/".$this->MODULE_ID, true, true);
        CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/images/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/images/".$this->MODULE_ID, true, true);
        CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/admin/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/", true, true);

        return true;
    }

    public function InstallPublic() {
        return true;
    }

    public function InstallEvents() {
        EventManager::getInstance()->registerEventHandler(
            "main",
            "OnAfterUserAuthorize",
            $this->MODULE_ID,
            "Wbs24\\Wbapi\\Update",
            "OnAfterUserAuthorizeHandler"
        );

        EventManager::getInstance()->registerEventHandler(
            "main",
            "OnAdminSaleOrderViewDraggable",
            $this->MODULE_ID,
            "Wbs24\\Wbapi\\OrdersEvents",
            "onInit"
        );

        EventManager::getInstance()->registerEventHandler(
            "main",
            "OnBuildGlobalMenu",
            $this->MODULE_ID,
            "Wbs24\\Wbapi\\Menu",
            "OnBuildGlobalMenuHandler"
        );

        return true;
    }

    // UnInstal functions
    public function UnInstallDB($arParams = []) {
        UnRegisterModule($this->MODULE_ID);

        return true;
    }

    public function UnInstallFiles() {
        DeleteDirFiles(__DIR__.'/tools/', $_SERVER['DOCUMENT_ROOT'].'/bitrix/tools/'.$this->MODULE_ID);
        DeleteDirFiles(__DIR__.'/js/', $_SERVER['DOCUMENT_ROOT'].'/bitrix/js/'.$this->MODULE_ID);
        DeleteDirFiles(__DIR__.'/css/', $_SERVER['DOCUMENT_ROOT'].'/bitrix/css/'.$this->MODULE_ID);
        DeleteDirFiles(__DIR__.'/images/', $_SERVER['DOCUMENT_ROOT'].'/bitrix/images/'.$this->MODULE_ID);
        DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/admin", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");

        return true;
    }

    public function UnInstallPublic() {
        return true;
    }

    public function UnInstallEvents() {
        EventManager::getInstance()->unRegisterEventHandler(
            "main",
            "OnAfterUserAuthorize",
            $this->MODULE_ID,
            "Wbs24\\Wbapi\\Update",
            "OnAfterUserAuthorizeHandler"
        );

        EventManager::getInstance()->unRegisterEventHandler(
            "main",
            "OnAdminSaleOrderViewDraggable",
            $this->MODULE_ID,
            "Wbs24\\Wbapi\\OrdersEvents",
            "onInit"
        );

        EventManager::getInstance()->unRegisterEventHandler(
            "main",
            "OnBuildGlobalMenu",
            $this->MODULE_ID,
            "Wbs24\\Wbapi\\Menu",
            "OnBuildGlobalMenuHandler"
        );

        return true;
    }

    public function DoInstall() {
        global $APPLICATION, $step;
        $keyGoodFiles = $this->InstallFiles();
        $keyGoodDB = $this->InstallDB();
        $keyGoodEvents = $this->InstallEvents();
        $keyGoodPublic = $this->InstallPublic();
        $APPLICATION->IncludeAdminFile(
            Loc::getMessage("SPER_INSTALL_TITLE"),
            $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/install.php"
        );
    }

    public function DoUninstall() {
        global $APPLICATION, $step;
        $keyGoodFiles = $this->UnInstallFiles();
        $keyGoodDB = $this->UnInstallDB();
        $keyGoodEvents = $this->UnInstallEvents();
        $keyGoodPublic = $this->UnInstallPublic();
        $APPLICATION->IncludeAdminFile(
            Loc::getMessage("SPER_UNINSTALL_TITLE"),
            $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/uninstall.php"
        );
    }
}
?>
