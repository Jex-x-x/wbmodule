<?php
namespace Wbs24\Wbapi;

class OrdersStack extends Stack
{
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
            $order = $resultOrder;
            $serializedOrder = serialize($order);
            $data['external_id'] = $externalId;
            $data['order'] = $serializedOrder;
            $data['account_index'] = $this->accountIndex;
            $this->Db->set('wbs24_wbapi_orders_stack', $data);
        }
    }

    public function get()
    {
        $serializedorders = $this->Db->get('wbs24_wbapi_orders_stack', [
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
            $this->Db->clear('wbs24_wbapi_orders_stack', $where = [
                'external_id' => $externalId,
                'account_index' => $this->accountIndex,
            ]);
        }
    }
}
