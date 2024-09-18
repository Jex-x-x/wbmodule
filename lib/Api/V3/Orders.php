<?php
namespace Wbs24\Wbapi\Api\V3;

use Bitrix\Main\SystemException;
use Bitrix\Main\Localization\Loc;
use Wbs24\Wbapi\Api;

class Orders extends Api
{
    protected $limit = 50;
    protected $barcodeAsOfferId;
    protected $dateFromTimestamp;

    public function __construct($objects = [])
    {
        parent::__construct($objects);

        $this->barcodeAsOfferId = ($this->wrappers->Option->get($this->moduleId, 'barcodeAsOfferId') == 'Y');
    }

    public function apiLaunch($data)
    {
        $this->dateFromTimestamp = $this->getTimestamp($data['period_days'] ?? 1);

        $allOrders = [];
        $qurrentOrdersQuantity = 0;
        $next = 0;
        do {
            $param = $this->getParam($data, $next);
            $orders = $this->getOrders($param);
            $ordersResult = $orders['orders'] ?? [];
            $allOrders = array_merge($allOrders, $ordersResult);
            $next = $orders['next'] ?? 0;
        } while ($next && $ordersResult);

        return $allOrders;
    }

    protected function getParam($data, $next)
    {
        $param = [
            'next' => $next,
            'limit' => $this->limit,
            'dateFrom' => $this->getTimestamp($data['period_days'] ?? 1),
        ];

        return $param;
    }

    protected function getOrders($param)
    {
        $requestUrl = $this->getRequestUrl('/api/v3/orders');
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
                "phone" => $order['user']['phone'] ?? '',
                "customer_email" => '',
                "name" => $order['user']['fio'] ?? '',
                "address" => [
                    "city" => $order['address']['city'] ?? '',
                    "address_tail" => $this->getAddress($order),
                    "comment" => '',
                ],
            ];

            $products[] = [
                "offer_id" => $this->barcodeAsOfferId ? $order['skus'] : $order['article'],
                "name" => 'Product',
                "price" => $order['convertedPrice'] / 100,
                "currency" => $order['convertedCurrencyCode'], // specific
                "discount_price" => 0,
                "quantity" => 1,
            ];

            if ($order['convertedCurrencyCode'] != 643) {
                $msg = 'Warning: The currency '.$order['convertedCurrencyCode'].' of order '.$order['id'].' don`t support';
                $this->createReport('error_log.txt', $msg);
                continue;
            }

            if (!$this->validateOrdercreateDate($order['createdAt'])) {
                $msg = 'Warning: The order '.$order['id'].' has a creation date that is not in the range of the requested order creation date';
                $this->createReport('error_log.txt', $msg);
                continue;
            }

            $convertedOrders[] = [
                "posting_number" => $order['id'],
                "order_group_id" => $order['orderUid'],
                "status" => '',
		"part" => $order['supplyId'],
                "in_process_at" => $order['createdAt'],
                "shipment_date" => '',
                "tracking_number" => '',
                "customer" => $customer,
                "products" => $products,
            ];
        }

        return $convertedOrders;
    }

    protected function validateOrderCreateDate($createDate)
    {
        $validate = true;
        $orderCreateDate = substr($createDate, 0, 10);
        $dateFrom = date("Y-m-d", $this->dateFromTimestamp);
        if (strtotime($orderCreateDate) < strtotime($dateFrom)) $validate = false;

        return $validate;
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
            $item = $order['address'][$type] ?? '';
            if (!$item) continue;

            if ($type == "entrance") {
                $item = Loc::getMessage(strtoupper($this->moduleId).".ENTRANCE").' '.$item;
            }
            if (
                $type == "area"
                && $item == $order['address']['province']
            ) continue;
            if (
                $type == "city"
                && $item == $order['address']['area']
            ) continue;

            $address .= ($address ? ', ' : '').$item;
        }

        return $address;
    }
}
