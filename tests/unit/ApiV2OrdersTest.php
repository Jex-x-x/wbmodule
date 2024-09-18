<?php
namespace Wbs24\Wbapi;

use Bitrix\Main\Loader;

class ApiV2OrdersTest extends BitrixTestCase
{
    public function testConvertOrders()
    {
        // входные параметры
        $orders = [
            [
                'orderId' => '13833711',
                'dateCreated' => '2021-02-20T16:50:33.365+03:00',
                'wbWhId' => 119408,
                'storeId' => 658434,
                'pid' => 0,
                'officeAddress' => 'г Ставрополь (Ставропольский край), Ленина 482/1',
                'OfficeLatitude' => 45.038605,
                'OfficeLongitude' => 41.905666,
                'deliveryAddress' => 'улица, дом, квартира',
                'deliveryAddressDetails' => [
                    'province' => 'Челябинская область',
                    'area' => 'Челябинск',
                    'city' => 'Челябинск',
                    'street' => '51-я улица Арабкира',
                    'home' => '10А',
                    'flat' => '42',
                    'entrance' => '3',
                    'longitude' => 44.519068,
                    'latitude' => 40.20192,
                ],
                'userInfo' => [
                    'userId' => 123,
                    'fio' => 'Иванов Иван Иванович',
                    'phone' => '79991112233',
                ],
                'chrtId' => 11111111,
                'barcode' => '6665956397512',
                'barcodes' => [
                    0 => '6665956397512',
                ],
                'scOfficesNames' => [
                    0 => 'СЦ Калуга',
                ],
                'status' => 0,
                'userStatus' => 0,
                'rid' => '100321840623',
                'totalPrice' => 5600,
                'currencyCode' => 643,
                'orderUID' => 'qwerty123456',
                'deliveryType' => 1,
            ],
        ];

        // результат для проверки
        $expectedResult = [
            [
                'posting_number' => $orders[0]['orderId'],
                'order_group_id' => $orders[0]['orderUID'],
                'status' => 'status_'.$orders[0]['status'],
                'in_process_at' => $orders[0]['dateCreated'],
                'shipment_date' => '',
                'tracking_number' => '',
                'customer' => [
                    'phone' => $orders[0]['userInfo']['phone'],
                    'customer_email' => '',
                    'name' => $orders[0]['userInfo']['fio'],
                    'address' => [
                        'city' => $orders[0]['deliveryAddressDetails']['city'],
                        'address_tail' =>
                            $orders[0]['deliveryAddressDetails']['province'].', '
                            .$orders[0]['deliveryAddressDetails']['city'].', '
                            .$orders[0]['deliveryAddressDetails']['street'].', '
                            .$orders[0]['deliveryAddressDetails']['home'].', '
                            .$orders[0]['deliveryAddressDetails']['flat'].', '
                            .'вход '.$orders[0]['deliveryAddressDetails']['entrance']
                        ,
                        'comment' => '',
                    ],
                ],
                'products' => [
                    [
                        'offer_id' => $orders[0]['chrtId'],
                        'name' => 'Product',
                        'price' => $orders[0]['totalPrice'] / 100,
                        'currency' => $orders[0]['currencyCode'],
                        'discount_price' => 0,
                        'quantity' => 1,
                        'barcode' => $orders[0]['barcode'],
                    ],
                ],
            ],
        ];

        // заглушки
        $OptionStub = $this->createMock(Wrappers\Option::class);
        $OptionStub->method('get')
            ->willReturn('');

        // вычисление результата
        // вызов protected метода
        $method = $this->getMethod('Wbs24\\Wbapi\\Api\\V2\\Orders', 'convertOrders');
        $object = new Api\V2\Orders([
            'Option' => $OptionStub,
        ]);
        $result = $method->invokeArgs($object, [$orders]);

        // проверка
        $this->assertEquals($expectedResult, $result);
    }
}
