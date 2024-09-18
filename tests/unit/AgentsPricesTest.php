<?php
namespace Wbs24\Wbapi;

class AgentsPricesTest extends BitrixTestCase
{
    public function _testUpdatePricesStep() // отключен
    {
        // входные параметры
        $skip = 0;
        $accountIndex = 3;

        // результат для проверки
        $expectedResult = 100;

        // заглушки
        $PricesStackStub = $this->createMock(Prices\Stack::class);
        $PricesStackStub->method('get')
            ->willReturn([
                [
                    'nmId' => 1,
                    'price' => 1000,
                ],
                [
                    'nmId' => 2,
                    'price' => 2000,
                ],
            ]);

        $ProductDirectoryStub = $this->createMock(ProductDirectory::class);
        $ProductDirectoryStub->method('get')
            ->willReturn([
                '12345' => [
                    'nmId' => 1,
                    'article' => '10',
                ],
                '23456' => [
                    'nmId' => 2,
                    'article' => '20',
                ],
            ]);

        $ProductStub = $this->createMock(Product::class);
        $ProductStub->method('getProductInfoByOfferId')
            ->will($this->onConsecutiveCalls(...[
                [
                    'id' => 100,
                    'package_ratio_value' => 1,
                ],
                [
                    'id' => 200,
                    'package_ratio_value' => 1,
                ],
            ]));

        $PricesHelperStub = $this->createMock(Prices\Helper::class);
        $PricesHelperStub->method('getPriceByProductId')
            ->will($this->onConsecutiveCalls(...[
                1100,
                2100,
            ]));

        $ApiControllerStub = $this->createMock(Api\Controller::class);
        $ApiControllerStub->expects($this->once()) // проверка
            ->method('action')
            ->with(
                $this->equalTo([
                    'action' => 'set_prices',
                    'account_index' => $accountIndex,
                    'prices' => [
                        [
                            'nmId' => 1,
                            'price' => 1100,
                        ],
                        [
                            'nmId' => 2,
                            'price' => 2100,
                        ],
                    ],
                ])
            );

        // вычисление результата
        $object = new Agents\Prices();
        $object->setDependences([
            'PricesStack' => $PricesStackStub,
            'ProductDirectory' => $ProductDirectoryStub,
            'Product' => $ProductStub,
            'PricesHelper' => $PricesHelperStub,
            'ApiController' => $ApiControllerStub,
        ], [], $accountIndex);
        $result = $object->updatePricesStep($skip, $accountIndex);

        // проверка
        $this->assertEquals($expectedResult, $result);
    }
}
