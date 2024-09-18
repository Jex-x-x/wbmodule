<?php
namespace Wbs24\Wbapi;

use Bitrix\Main\SystemException;

class Wrappers
{
    use Exception; // trait

    public $lastError;

    public function __construct($objects = [], $dontDie = false)
    {
        $this->getObjects($objects, $dontDie);
    }

    protected function getObjects($objects, $dontDie)
    {
        try {
            $needObjects = [
                'CIBlock', // Bitrix
                'CIBlockElement', // Bitrix
                'CIBlockProperty', // Bitrix
                'Option', // Bitrix D7
                'CAgent', // Bitrix
                'CCatalog', // Bitrix
                'CCatalogSKU', // Bitrix
                'Order', // Bitrix D7
                'GroupTable', // Bitrix D7
                'Price', // Bitrix D7
                'CCatalogProduct', // Bitrix
                'Curl',

                // для Update.php
                'CUpdateClientPartner',
            ];

            foreach ($needObjects as $obj) {
                if (!empty($objects[$obj])) {
                    $this->{$obj} = $objects[$obj];
                } else {
                    $className = __NAMESPACE__."\\Wrappers\\".$obj;

                    if ($obj == 'Curl') {
                        $this->{$obj} = new $className($dontDie);
                    } else {
                        $this->{$obj} = new $className();
                    }
                }
            }
        } catch (SystemException $exception) {
            $this->exceptionHandler($exception, $dontDie);
        }
    }
}
