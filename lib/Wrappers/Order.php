<?php
namespace Wbs24\Wbapi\Wrappers;

use Bitrix\Main\SystemException;
use Bitrix\Main\Loader;
use Wbs24\Wbapi\Exception;

class Order {
    use Exception; // trait

    public function __construct() {
        try {
            if (!Loader::includeModule('sale')) throw new SystemException("Module sale don`t installed");
        } catch (SystemException $exception) {
            $this->exceptionHandler($exception);
        }
    }

    public function getList(...$args)
    {
        return \Bitrix\Sale\Order::getList(...$args);
    }

    public function load(...$args) {
        return \Bitrix\Sale\Order::load(...$args);
    }

    public function create(...$args)
    {
        return \Bitrix\Sale\Order::create(...$args);
    }
}
