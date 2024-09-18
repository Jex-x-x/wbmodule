<?php
namespace Wbs24\Wbapi;

use Bitrix\Main\Loader;

class OrdersCombineTest extends BitrixTestCase
{
    public function testGetCombinedExternalIds()
    {
        // входные параметры
        $parentExternalIds = [
            1111,
            2222,
            3333,
        ];

        // результат для проверки
        $expectedResult = [
            222201,
            222202,
        ];

        // заглушки
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

        $OrdersDirectoryStub = $this->createMock(Orders\Directory::class);
        $fetchResults = [
            [],
            [
                [
                    'external_id' => 222201,
                ],
                [
                    'external_id' => 222202,
                ],
            ],
            [],
        ];
        $OrdersDirectoryStub->method('get')
            ->will($this->onConsecutiveCalls(...$fetchResults));

        // вычисление результата
        $object = new Orders\Combine([
            'Option' => $OptionStub,
            'OrdersDirectory' => $OrdersDirectoryStub,
        ]);
        $result = $object->getCombinedExternalIds($parentExternalIds);

        // проверка
        $this->assertEquals($expectedResult, $result);
    }

    public function testGetNotEnoughProdicts()
    {
        // входные параметры
        $mpProducts = [
            1111 => 1,
            2222 => 5,
            3333 => 2,
        ];
        $basketProducts = [
            1111 => 1,
            2222 => 2,
        ];

        // результат для проверки
        $expectedResult = [
            2222 => 3,
            3333 => 2,
        ];

        // заглушки
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

        // вычисление результата
        // вызов protected метода
        $method = $this->getMethod('Wbs24\\Wbapi\\Orders\\Combine', 'getNotEnoughProdicts');
        $object = new Orders\Combine([
            'Option' => $OptionStub,
        ]);
        $result = $method->invokeArgs($object, [$mpProducts, $basketProducts]);

        // проверка
        $this->assertEquals($expectedResult, $result);
    }
}
