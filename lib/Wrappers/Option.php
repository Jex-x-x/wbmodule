<?php
namespace Wbs24\Wbapi\Wrappers;

class Option {
    protected const CURRENT_MODULE_ID = 'wbs24.wbapi';

    protected $prefix = '';
    protected $accountIndex = 1;

    public function get($moduleId, $name, $default = '', $siteId = false) {
        $fullName = $this->getFullName($moduleId, $name);

        return \Bitrix\Main\Config\Option::get($moduleId, $fullName, $default, $siteId);
    }

    public function set($moduleId, $name, $value = '', $siteId = '') {
        $fullName = $this->getFullName($moduleId, $name);

        return \Bitrix\Main\Config\Option::set($moduleId, $fullName, $value, $siteId);
    }

    public function setPrefix($accountIndex = 1)
    {
        $accountIndex = is_numeric($accountIndex) ? $accountIndex : 1;
        $prefixNumber = intval($accountIndex);
        if ($prefixNumber == 1) {
            $prefix = '';
        } else {
            $prefix = 'a'.$prefixNumber.'_';
        }

        $this->accountIndex = $accountIndex;
        $this->prefix = $prefix;

        return $prefix;
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    public function getAccountIndex()
    {
        return $this->accountIndex;
    }

    public function getFullName($moduleId, $name)
    {
        $fullName = ($moduleId == self::CURRENT_MODULE_ID) ? $this->prefix.$name : $name;

        return $fullName;
    }
}
