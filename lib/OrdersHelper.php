<?php
namespace Wbs24\Wbapi;

trait OrdersHelper
{
    protected $xmlIdPrefix = 'WB';

    protected function isOrderFromModule($orderId)
    {
        $filter = [
            '%=XML_ID' => $this->xmlIdPrefix.'%',
            'ID' => $orderId,
        ];

        $orders = $this->wrappers->Order->getList([
            'select' => ['ID', 'STATUS_ID'],
            'filter' => $filter,
            'order' => ['ID' => 'ASC'],
        ]);

        if ($orderResult = $orders->fetch()) {
            return $orderResult['ID'];
        }

        return false;
    }

    protected function getAccountIndexByXmlId($xmlId)
    {
        preg_match('/'.$this->xmlIdPrefix.'(\d*)_(.+)/', $xmlId, $matches);
        $accountIndex = $matches[1] ?: 1;

        return $accountIndex;
    }
}
