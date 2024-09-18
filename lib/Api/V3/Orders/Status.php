<?php
namespace Wbs24\Wbapi\Api\V3\Orders;

use Wbs24\Wbapi\Api;

class Status extends Api
{
    public function apiLaunch($data)
    {
        /**
         * в $data ожидается массив id заказов wb вида:
         * $data['order_ids'] = [
         *   123, 345, 567
         * ];
         **/
        $requestUrl = $this->getRequestUrl('/api/v3/orders/status');
        $result = $this->createRequest($requestUrl, [
            'orders' => $data['order_ids'],
        ], [
            'post' => true,
        ]);
        $orders = $result['orders'] ?? [];

        return $orders;
    }

    public function processAction($responseData)
    {
        $orders = $responseData['result'] ?? [];
        $externalIdsToStatus = [];
        foreach ($orders as $order) {
            $externalIdsToStatus[$order['id']] = $order['wbStatus'];
        }

        return $externalIdsToStatus;
    }
}
