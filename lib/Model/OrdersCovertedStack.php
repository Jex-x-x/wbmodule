<?php
namespace Wbs24\Wbapi\Model;

use Wbs24\Wbapi\Stack;

class OrdersCovertedStack extends Stack
{
    const TABLE_NAME = 'wbs24_wbapi_orders_stack_converted';

    public function addOrdersToStack($ordersFromTradingPlatform)
    {
        return $this->add($ordersFromTradingPlatform);
    }

    public function getOrders()
    {
        return $this->get();
    }

    public function cleanStack($externalIds)
    {
        return $this->clean($externalIds);
    }

    public function add($ordersFromTradingPlatform)
    {
        $data = [];
        foreach ($ordersFromTradingPlatform as $key => $resultOrder) {
            $externalId = $resultOrder['posting_number'];
            $groupExternalIds = $resultOrder['group_external_ids'];
            $order = $resultOrder;
            $serializedOrder = serialize($order);
            $data['external_id'] = $externalId;
            $data['order'] = $serializedOrder;
            $data['account_index'] = $this->accountIndex;
            $this->Db->set(self::TABLE_NAME, $data);
        }
    }

    public function get()
    {
        $serializedorders = $this->Db->get(self::TABLE_NAME, [
            'account_index' => $this->accountIndex,
        ]);
        foreach ($serializedorders as $key => $resultOrder) {
            $unserializedOrder = $this->safeUnserialize($resultOrder['order']);
            $orders[] = $unserializedOrder;
        }

        return $orders;
    }

    public function clean($externalIds = [])
    {
        foreach ($externalIds as $externalId) {
            $this->Db->clear(self::TABLE_NAME, $where = [
                'external_id' => $externalId,
                'account_index' => $this->accountIndex,
            ]);
        }
    }
}
