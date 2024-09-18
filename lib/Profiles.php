<?php
namespace Wbs24\Wbapi;

use Bitrix\Main\Localization\Loc;
use Wbs24\Wbapi\Settings;
/**
 * Класс управления профилями
 */
class Profiles
{
    public function __construct($objects = [])
    {
        $this->main = $objects['Main'] ?? new Main();
        $this->moduleId = $this->main->getModuleId();
        $this->wrappers = new Wrappers($objects);

        $this->settings = $objects['Settings'] ?? new Settings();
    }

    public function getList()
    {
        return $this->settings->getAccounts();
    }
}
