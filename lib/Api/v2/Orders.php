<?php
namespace Wbs24\Wbapi\Api\V2;

use Bitrix\Main\SystemException;
use Bitrix\Main\Localization\Loc;
use Wbs24\Wbapi\Api;

// depricated - не используется
class Orders extends Api
{
    protected $take = 50;

    public function apiLaunch($data)
    {
        $allOrders = [];
        $qurrentOrdersQuantity = 0;
        $skip = 0;
        do {
            $param = $this->getParam($data, $skip);
            $orders = $this->getOrders($param);
            $ordersResult = $orders['orders'] ?? [];
            $allOrders = array_merge($allOrders, $ordersResult);
            $qurrentOrdersQuantity = $orders['total'] ?? 0;
            if ($qurrentOrdersQuantity == $this->take) {
                $skip += $this->take;
            }
        } while ($qurrentOrdersQuantity == $this->take);

        return $allOrders;
    }

    protected function getParam($data, $skip)
    {
        $param = [
            'skip' => $skip,
            'take' => $this->take,
            'date_start' => $this->getPeriod($data['period_days'] ?? 1),
        ];

        if ($data['order_id']) {
            $param['id'] = $data['order_id'];
        }

        return $param;
    }

    protected function getOrders($param)
    {
        $requestUrl = $this->getRequestUrl('/api/v2/orders');
        $orders = $this->createRequest($requestUrl, $param);

        return $orders;
    }

    public function processAction($responseData)
    {
        $orders = $responseData['result'] ?? [];

        return $this->convertOrders($orders);
    }

    protected function convertOrders($orders)
    {
        $convertedOrders = [];
        foreach ($orders as $order) {
            $products = [];
            $customer = [];
            $status = '';
            $statusSuffix = 'status_';

            $customer = [
                "phone" => $order['userInfo']['phone'] ?? '',
                "customer_email" => '',
                "name" => $order['userInfo']['fio'] ?? '',
                "address" => [
                    "city" => $order['deliveryAddressDetails']['city'] ?? '',
                    "address_tail" => $this->getAddress($order),
                    "comment" => '',
                ],
            ];

            $products[] = [
                "offer_id" => $order['chrtId'],
                "barcode" => $order['barcode'], // specific
                "name" => 'Product',
                "price" => $order['totalPrice'] / 100,
                "currency" => $order['currencyCode'], // specific
                "discount_price" => 0,
                "quantity" => 1,
            ];

            if ($order['currencyCode'] != 643) {
                $msg = 'Warning: The currency of order with id = '.$order['chrtId'].' don`t support';
                $this->createReport('error_log.txt', $msg);
                continue;
            }

            $convertedOrders[] = [
                "posting_number" => $order['orderId'],
                "order_group_id" => $order['orderUID'],
                "status" => $statusSuffix.$order['status'],
                "in_process_at" => $order['dateCreated'],
                "shipment_date" => '',
                "tracking_number" => '',
                "customer" => $customer,
                "products" => $products,
            ];
        }

        return $convertedOrders;
    }

    protected function getAddress($order)
    {
        $address = '';
        $types = [
            "province",
            "area",
            "city",
            "street",
            "home",
            "flat",
            "entrance",
        ];
        foreach ($types as $type) {
            $item = $order['deliveryAddressDetails'][$type] ?? '';
            if (!$item) continue;

            if ($type == "entrance") {
                $item = Loc::getMessage(strtoupper($this->moduleId).".ENTRANCE").' '.$item;
            }
            if (
                $type == "area"
                && $item == $order['deliveryAddressDetails']['province']
            ) continue;
            if (
                $type == "city"
                && $item == $order['deliveryAddressDetails']['area']
            ) continue;

            $address .= ($address ? ', ' : '').$item;
        }

        return $address;
    }
}
