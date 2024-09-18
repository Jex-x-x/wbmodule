<?php
namespace Wbs24\Wbapi;

use Bitrix\Main\Loader;

class OrdersTest extends BitrixTestCase
{
    public function testGetProductFields()
    {
        // входные параметры
        $product = [
            'offer_id' => ['article228'],
            'name' => 'Аэратор Timo M28X1 хром',
            'price' => 240,
            'discount_price' => 0,
            'quantity' => 1,
        ];
        // результат для проверки
        $expectedResult = [
            'PRODUCT_ID' => 97885,
            'NAME' => 'Аэратор Timo M28X1 хром',
            'BASE_PRICE' => 240,
            'DISCOUNT_PRICE' => 0,
            'PRICE' => 240,
            'CURRENCY' => 'RUB',
            'QUANTITY' => 1,
            'CUSTOM_PRICE' => 'Y',
            'LID' => 's1',
            'PRODUCT_PROVIDER_CLASS' => '\Bitrix\Catalog\Product\CatalogProvider',
            'DETAIL_PAGE_URL' => 'http://test.ru/catalog/97885/',
            'CAN_BUY' => false
        ];

        // заглушки
        Loader::includeModule('iblock');

        // для конструктора
        $OptionStub = $this->createMock(Wrappers\Option::class);
        $OptionStub->method('get')
            ->will($this->returnCallback(
                function ($moduleId, $option) {
                    $optionsResults = [
                        'siteId' => 's1',
                        'skuPropertyForProducts' => 'ID',
                        'skuPropertyForProductOffers' => 'DEMOPROP',
                    ];

                    return $optionsResults[$option] ?? '';
                }
            ));

        // для getAllTradeCatalogs()
        $CIBlockResultForCIBlockStub = $this->createMock(\CIBlockResult::class);
        $fetchResults = [
            [
                'ID' => 1,
            ],
            [
                'ID' => 2,
            ],
            false,
        ];
        $CIBlockResultForCIBlockStub->method('Fetch')
            ->will($this->onConsecutiveCalls(...$fetchResults));

        $CIBlockStub = $this->createMock(Wrappers\CIBlock::class);
        $CIBlockStub->method('GetList')
            ->willReturn($CIBlockResultForCIBlockStub);

        $CCatalogStub = $this->createMock(Wrappers\CCatalog::class);
        $CCatalogStub->method('GetByIDExt')
            ->will($this->onConsecutiveCalls(...[
                [
                    'CATALOG_TYPE' => 'X',
                    'PRODUCT_IBLOCK_ID' => 1,
                    'OFFERS_IBLOCK_ID' => 2,
                ],
                [
                    'CATALOG_TYPE' => '',
                ],
            ]));

        // для getDetailedInformationAboutProduct()
        $CIBlockResultStub1 = $this->createMock(\CIBlockResult::class);
        $fetchResults = [
            false,
        ];
        $CIBlockResultStub1->method('GetNext')
            ->will($this->onConsecutiveCalls(...$fetchResults));

        // для getDetailedInformationAboutProductOffers()
        $CIBlockResultForCIBlockPropertyStub = $this->createMock(\CIBlockResult::class);
        $fetchResults = [
            [
                'PROPERTY_TYPE' => 'S',
                'CODE' => 'DEMOPROP',
                'NAME' => 'Demo property name',
            ],
            false,
        ];
        $CIBlockResultForCIBlockPropertyStub->method('Fetch')
            ->will($this->onConsecutiveCalls(...$fetchResults));

        $CIBlockPropertyStub = $this->createMock(Wrappers\CIBlockProperty::class);
        $CIBlockPropertyStub->method('GetList')
            ->willReturn($CIBlockResultForCIBlockPropertyStub);

        $CIBlockResultStub2 = $this->createMock(\CIBlockResult::class);
        $fetchResults = [
            [
                'ID' => 97885,
                'DETAIL_PAGE_URL' => 'http://test.ru/catalog/97885/',
                'QUANTITY' => 0,
                'CAN_BUY_ZERO' => 'N',
                'QUANTITY_TRACE' => 'Y'
            ],
            false,
        ];
        $CIBlockResultStub2->method('GetNext')
            ->will($this->onConsecutiveCalls(...$fetchResults));

        $CCatalogSKUStub = $this->createMock(Wrappers\CCatalogSKU::class);
        $CCatalogSKUStub->method('GetProductInfo')
            ->willReturn([
                'ID' => 80770,
            ]);

        $CIBlockResultStub3 = $this->createMock(\CIBlockResult::class);
        $fetchResults = [
            [
                'ACTIVE' => 'Y',
            ],
            false,
        ];
        $CIBlockResultStub3->method('GetNext')
            ->will($this->onConsecutiveCalls(...$fetchResults));

        // для всех функций
        $CIBlockElementStub = $this->createMock(Wrappers\CIBlockElement::class);
        $CIBlockElementStub->method('GetList')
            ->will($this->onConsecutiveCalls(...[$CIBlockResultStub1, $CIBlockResultStub2]));
        $CIBlockElementStub->method('GetByID')
            ->willReturn($CIBlockResultStub3);

        // вычисление результата
        // вызов protected метода
        $method = $this->getMethod('Wbs24\\Wbapi\\Orders', 'getProductFields');
        $object = new Orders([
            'Option' => $OptionStub,
            'CIBlock' => $CIBlockStub,
            'CCatalog' => $CCatalogStub,
            'CIBlockProperty' => $CIBlockPropertyStub,
            'CCatalogSKU' => $CCatalogSKUStub,
            'CIBlockElement' => $CIBlockElementStub,
        ]);
        $result = $method->invokeArgs($object, [$product]);

        // проверка
        $this->assertEquals($expectedResult, $result);
    }

    public function testChangeOrderStatus()
    {
        // входные параметры
        $orderId = 1;
        $statusOnTradingPlatform = 'awaiting_packaging';

        // результат для проверки
        $expectedResult = true;

        // заглушки
        Loader::includeModule('sale');

        // Bitrix\Sale\Order
        $ResultStub = $this->createMock(\Bitrix\Sale\Result::class);
        $ResultStub->method('isSuccess')
            ->willReturn(true);

        $PaymentCollectionStub = $this->createMock(\Bitrix\Sale\Payment::class);
        $PaymentCollectionStub->method('setPaid')
            ->willReturn(true);

        $ExemplarOrderStub = $this->createMock(\Bitrix\Sale\Order::class);
        $ExemplarOrderStub->method('getField')
            ->willReturn("N");
        $ExemplarOrderStub->method('setField')
            ->willReturn(true);
        $ExemplarOrderStub->method('save')
            ->willReturn($ResultStub);
        $ExemplarOrderStub->method('isPaid')
            ->willReturn(false);
        $ExemplarOrderStub->method('isShipped')
            ->willReturn(true);
        $ExemplarOrderStub->method('isCanceled')
            ->willReturn(true);
        $ExemplarOrderStub->method('getPaymentCollection')
            ->willReturn([$PaymentCollectionStub]);

        $OrderStub = $this->createMock(Wrappers\Order::class);
        $OrderStub->method('load')
            ->willReturn($ExemplarOrderStub);

        // Option
        $OptionStub = $this->createMock(Wrappers\Option::class);
        $OptionStub->method('get')
            ->will($this->returnCallback(
                function ($moduleId, $option) {
                    $suffix = 'status_';
                    $optionsResults = [
                        'siteId' => 's1',
                        'skuPropertyForProducts' => 'ID',
                        'skuPropertyForProductOffers' => 'DEMOPROP',
                        'status_NEW' => 'N',
                        'paymentFlag' => $suffix.'awaiting_packaging,'.$suffix.'awaiting_deliver',
                    ];

                    return $optionsResults[$option] ?? '';
                }
            ));

        // вычисление результата
        $object = new Orders([
            'Order' => $OrderStub,
            'Option' => $OptionStub,
        ]);
        $result = $object->changeOrderStatus($orderId, $statusOnTradingPlatform);

        // проверка
        $this->assertEquals($expectedResult, $result);
    }

    public function testApplyTemplate()
    {
        // входные параметры
        $template = 'Заказ #SHIPMENT_ID#';
        $resultOrder = [
            'posting_number' => '111222333',
        ];

        // результат для проверки
        $expectedResult = 'Заказ 111222333';

        // заглушки
        // Option
        $OptionStub = $this->createMock(Wrappers\Option::class);
        $OptionStub->method('get')
            ->willReturn('');

        // вычисление результата
        // вызов protected метода
        $method = $this->getMethod('Wbs24\\Wbapi\\Orders', 'applyTemplate');
        $object = new Orders([
            'Option' => $OptionStub,
        ]);
        $result = $method->invokeArgs($object, [$template, $resultOrder]);

        // проверка
        $this->assertEquals($expectedResult, $result);
    }

    public function testGetFilterByCompletedStatuses()
    {
        // входные параметры
        $completedStatuses = [
            'cancelled',
            'delivered',
            'not_accepted',
        ];

        // результат для проверки
        $expectedResult = ['!=STATUS_ID' => [
            'F',
            'ZS',
            'OT',
        ]];

        // заглушки
        // Option
        $OptionStub = $this->createMock(Wrappers\Option::class);
        $OptionStub->method('get')
            ->will($this->returnCallback(
                function ($moduleId, $option) {
                    $statusSuffix = "status_";
                    $optionsResults = [
                        'siteId' => 's1',
                        'skuPropertyForProducts' => 'ID',
                        'skuPropertyForProductOffers' => 'DEMOPROP',
                        $statusSuffix.'cancelled' => '',
                        $statusSuffix.'delivered' => 'F',
                        $statusSuffix.'not_accepted' => 'ZS',
                        'disallowStatuses' => 'nothing,OT,F',
                    ];

                    return $optionsResults[$option] ?? '';
                }
            ));

        // вычисление результата
        // вызов protected метода
        $method = $this->getMethod('Wbs24\\Wbapi\\Orders', 'getFilterByCompletedStatuses');
        $object = new Orders([
            'Option' => $OptionStub,
        ]);
        $object->setCompletedStatuses($completedStatuses);
        $result = $method->invokeArgs($object, []);

        // проверка
        $this->assertEquals($expectedResult, $result);
    }
}
