<?php
namespace Wbs24\Wbapi;

class Main {
    protected $moduleId;
    public $lastError;

    public function __construct()
    {
        $this->moduleId = basename(__DIR__);
    }

    public function getModuleId()
    {
        return $this->moduleId;
    }

    public function isHttps()
    {
        return $_SERVER['HTTPS'] == 'on';
    }

    public function checkRights()
    {
        global $APPLICATION;

        $rights = $APPLICATION->GetGroupRight($this->moduleId);

        return ($rights == 'W') ? true : false;
    }
}

require_once('autoload.php');
?>
