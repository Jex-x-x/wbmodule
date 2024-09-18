<?php
namespace Wbs24\Wbapi;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

class OrdersTab
{
    use Exception; // trait
    use Accounts; // trait

    protected const XML_ID_PREFIX = 'WB';

    public function __construct($objects = [])
    {
        try {
            $this->main = $objects['Main'] ?? new Main();
            $this->moduleId = $this->main->getModuleId();
            $this->wrappers = new Wrappers($objects);

            $this->suffix = strtoupper($this->moduleId);

            if (!Loader::IncludeModule('sale')) {
                throw new SystemException("Sale module isn`t installed");
            }
        } catch (SystemException $exception) {
            $this->exceptionHandler($exception);
        }
    }

    public function loadJs()
    {
        echo '<script src="/bitrix/js/wbs24.wbapi/ordersTab.js?'.time().'"></script>';
    }

    public static function onInit()
    {
        $ordersTab = new OrdersTab();

        // проверка прав доступа
        if (!$ordersTab->main->checkRights()) return;

        $ordersTab->loadJs();

        return array(
            "TABSET" => "Wbs24WbOrdersTab",
            "GetTabs" => array("Wbs24\\Wbapi\\OrdersTab", "getTabs"),
            "ShowTab" => array("Wbs24\\Wbapi\\OrdersTab", "showTab"),
            "Action" => array("Wbs24\\Wbapi\\OrdersTab", "myaction"),
            "Check" => array("Wbs24\\Wbapi\\OrdersTab", "check"),
        );
    }

    public static function myaction($arArgs)
    {
        // Действие после сохранения заказа. Возвращаем true / false
        // Сообщение $GLOBALS["APPLICATION"]->ThrowException("Ошибка!!!", "ERROR");

        return true;
    }

    public static function check($arArgs)
    {
        // Проверки перед сохранением. Возвращаем true / false

        return true;
    }

    public static function getTabs($arArgs)
    {
        $ordersTab = new OrdersTab();
        $traidingPlatformOrder = $ordersTab->checkIsOrderFromTraidingPlatform($arArgs['ID']);
        $suffix = $ordersTab->suffix;

        if ($traidingPlatformOrder) {
            return [
                [
                    "DIV" => "edit1",
                    "TAB" => Loc::getMessage($suffix.'.COLLECT_ORDER_ON_MP'),
                    "ICON" => "sale",
                    "TITLE" => Loc::getMessage($suffix.'.TITLE'),
                    "SORT" => 1
                ]
            ];
        }
    }

    public function checkIsOrderFromTraidingPlatform($orderId)
    {
        $filter = [
            '%=XML_ID' => self::XML_ID_PREFIX.'%',
            'ID' => $orderId,
        ];

        $orders = \Bitrix\Sale\Order::getList([
            'select' => ['ID', 'STATUS_ID'],
            'filter' => $filter,
            'order' => ['ID' => 'ASC'],
        ]);

        if ($orderResult = $orders->fetch()) {
            return $orderResult['ID'];
        }

        return false;
    }

    public static function showTab($divName, $arArgs, $bVarsFromForm)
    {
        if ($divName == "edit1") {
            $ordersTab = new OrdersTab();
            $suffix = $ordersTab->suffix;

            echo Loc::getMessage($suffix.".ORDERS_TAB_NOTE");
            ?>

            <style>
            table {
                font-size: inherit !important;
            }
            </style>
			
			<?
        }
    }

    protected function getOrderProperties($orderId)
    {
        $order = \Bitrix\Sale\Order::load($orderId);
        $collection = $order->getPropertyCollection();

        $xmlId = $order->getField("XML_ID");
        $accountIndex = $this->getAccountIndexByXmlId($xmlId);
        $this->setAccount($accountIndex);

        $externalIdProp = $collection->getItemByOrderPropertyId(
            intval($this->wrappers->Option->get($this->moduleId, 'propertyOfExternalOrderNumber'))
        );
        $externalId = $externalIdProp->getValue();

        $shipmentDateProp = $collection->getItemByOrderPropertyId(
            intval($this->wrappers->Option->get($this->moduleId, 'propertyOfShipmentDate'))
        );
        $shipmentDate = $shipmentDateProp->getValue();

        return [
            'external_id' => $externalId,
            'account_index' => $accountIndex,
            'shipment_date' => $shipmentDate,
        ];
    }

    protected function getAccountIndexByXmlId($xmlId)
    {
        preg_match('/'.self::XML_ID_PREFIX.'(\d*)_(.+)/', $xmlId, $matches);
        $accountIndex = $matches[1] ?: 1;

        return $accountIndex;
    }
}
