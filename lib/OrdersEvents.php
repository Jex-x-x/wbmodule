<?php
namespace Wbs24\Wbapi;

use Wbs24\Wbapi\Orders\Combine;
use Bitrix\Main\Localization\Loc;

class OrdersEvents
{
    use Accounts;
    use OrdersHelper;

    protected $externalIdPropName = 'propertyOfExternalOrderNumber';

    public function __construct($objects = [])
    {
        $this->main = $objects['Main'] ?? new Main();
        $this->moduleId = $this->main->getModuleId();
        $this->wrappers = new Wrappers($objects);
        $this->suffix = strtoupper($this->moduleId);
    }

    public static function onInit()
    {
        $orderId = $_REQUEST['ID'];
        $OrdersEvents = new OrdersEvents();
        if (
            $OrdersEvents->isOrderFromModule($orderId)
            && $OrdersEvents->processGetGroupedExternalIds($orderId)
        ) {
            return [
                "BLOCKSET" => "Wbs24\\Wbapi\\OrdersEvents",
                "getBlocksBrief" => ["Wbs24\\Wbapi\\OrdersEvents", "getBlockCaption"],
                "getBlockContent" => ["Wbs24\\Wbapi\\OrdersEvents", "getBlockContent"],
            ];
        }
    }

    public static function getBlockCaption($args)
    {
        $OrdersEvents = new OrdersEvents();
        return [
            'custom' => ['TITLE' => Loc::getMessage($OrdersEvents->suffix.".BLOCK_CAPTION"),]
        ];
    }

    public static function getBlockContent($blockCode, $selectedTab, $args)
    {
        $orderId = $_REQUEST['ID'];
        $OrdersEvents = new OrdersEvents();
        return $OrdersEvents->processGetGroupedExternalIds($orderId);
    }

    protected function processGetGroupedExternalIds($orderId)
    {
        $order = $this->wrappers->Order->load($orderId);
        $dependencies = $this->prepareNeedObjects($order);
        $parentExternalId = $this->getParentExternalId($order);
        $groupedExternalIds = $this->getGroupedExternalIds($parentExternalId);

        return $this->prepareHtml($groupedExternalIds, $parentExternalId);
    }

    protected function getGroupedExternalIds($parentExternalId)
    {
        if (!$parentExternalId) return [];

        return $this->OrdersCombine->getCombinedExternalIds([$parentExternalId]);
    }

    protected function prepareHtml($groupedExternalIds, $parentExternalId)
    {
        if (!$groupedExternalIds || !$parentExternalId) return '';

        $content = '<table border="0" cellspacing="0" cellpadding="0" width="100%" class="adm-detail-content-table edit-table"><tbody>'
            .'<tr >'
                .'<td class="adm-detail-content-cell-l" width="40%" valign="top">'. Loc::getMessage($this->suffix.".LIST_ASSEMBLY_TASKS") .'</td>'
                .'<td class="adm-detail-content-cell-r">'. $parentExternalId .'</td>'
            .'</tr>'
        ;

        foreach ($groupedExternalIds as $groupedExternalId) {
            $content .=
                '<tr >'
                    .'<td class="adm-detail-content-cell-l" width="40%" valign="top"></td>'
                    .'<td class="adm-detail-content-cell-r">'. $groupedExternalId .'</td>'
                .'</tr>'
            ;
        }

        $content .= '</tbody></table>';

        return $content;
    }

    protected function prepareNeedObjects(&$order)
    {
        $xmlId = $order->getField('XML_ID');
        $accountIndex = $this->getAccountIndexByXmlId($xmlId);
        $dependencies = $this->getDependencies($accountIndex);
        $this->OrdersCombine = new Combine($dependencies);
    }

    protected function getParentExternalId(&$order) {
        $propertyCollection = $order->getPropertyCollection();
        $externalIdProp = $propertyCollection
            ->getItemByOrderPropertyId(
                $this->wrappers->Option->get(
                    $this->moduleId, $this->externalIdPropName
                )
            );
        if (!$externalIdProp) return '';

        return $externalIdProp->getValue();
    }
}
