<?php
namespace Wbs24\Wbapi;

use Bitrix\Main\Loader;

class OrdersStackTest extends BitrixTestCase
{
    public function testGetOrders()
    {
        // входные параметры
        $accountIndex = 1;

        // результат для проверки
        $expectedResult = [
            0 => [
                'posting_number' => '19002108-0002-1',
            ],
        ];

        // заглушки
        $DbStub = $this->createMock(Db::class);
        $DbStub->method('get')
            ->willReturn([
                0 => [
                    'order' => 'a:19:{s:14:"posting_number";s:15:"19002108-0002-1";s:8:"order_id";i:723892395;s:12:"order_number";s:13:"19002108-0002";s:6:"status";s:16:"awaiting_deliver";s:15:"delivery_method";a:6:{s:2:"id";i:141214;s:4:"name";s:59:"Боксберри Доставка в ПВЗ. Москва";s:12:"warehouse_id";i:23047819468000;s:9:"warehouse";s:47:"Доставка со своего склада";s:15:"tpl_provider_id";i:16;s:12:"tpl_provider";s:18:"Боксберри";}s:15:"tracking_number";s:0:"";s:20:"tpl_integration_type";s:12:"3pl_tracking";s:13:"in_process_at";s:20:"2022-06-13T14:50:22Z";s:13:"shipment_date";s:20:"2022-06-14T14:00:00Z";s:15:"delivering_date";N;s:12:"cancellation";a:6:{s:16:"cancel_reason_id";i:0;s:13:"cancel_reason";s:0:"";s:17:"cancellation_type";s:0:"";s:20:"cancelled_after_ship";b:0;s:26:"affect_cancellation_rating";b:0;s:22:"cancellation_initiator";s:0:"";}s:8:"customer";a:5:{s:11:"customer_id";i:19002108;s:14:"customer_email";s:0:"";s:5:"phone";s:11:"79999999999";s:7:"address";a:11:{s:12:"address_tail";s:118:"Россия, 143985 Московская обл, Балашиха, Ленина (Саввино мкр.) ул, д.99";s:4:"city";s:16:"Балашиха";s:7:"comment";s:0:"";s:7:"country";s:12:"Россия";s:8:"district";s:0:"";s:6:"region";s:20:"Московская";s:8:"zip_code";s:0:"";s:8:"latitude";d:99.7287674;s:9:"longitude";d:99.03125763;s:8:"pvz_code";i:121102;s:17:"provider_pvz_code";s:5:"02324";}s:4:"name";s:35:"Аааааа Ааааааааааа";}s:8:"products";a:1:{i:0;a:7:{s:5:"price";s:11:"1499.000000";s:8:"offer_id";s:7:"1047972";s:4:"name";s:34:"Clariti 1 day 30 шт (8.6, -3.00)";s:3:"sku";i:591990288;s:8:"quantity";i:1;s:14:"mandatory_mark";a:0:{}s:13:"currency_code";s:3:"RUB";}}s:9:"addressee";a:2:{s:4:"name";s:36:"Ааааааааааа Аааааа ";s:5:"phone";s:11:"79999999999";}s:8:"barcodes";N;s:14:"analytics_data";N;s:14:"financial_data";N;s:10:"is_express";b:0;s:12:"requirements";a:4:{s:22:"products_requiring_gtd";a:0:{}s:26:"products_requiring_country";a:0:{}s:33:"products_requiring_mandatory_mark";a:0:{}s:23:"products_requiring_rnpt";a:0:{}}}',
                ],
            ]);

        // вычисление результата
        $object = new OrdersStack($accountIndex, [
            'Db' => $DbStub,
        ]);
        $result = $object->getOrders();

        // проверка
        $this->assertEquals($expectedResult[0]['posting_number'], $result[0]['posting_number']);
    }
}
