<?php
namespace Wbs24\Wbapi;

use Bitrix\Main\Loader;

class CurlExecTest extends BitrixTestCase
{
    public function testGetStatuses()
    {
        // входные параметры
        $accountIndex = 2;

        // результат для проверки
        $expectedResult = '';

        // заглушки
        $OrdersStub = $this->createMock(Orders::class);
        $OrdersStub->method('getOrderIdsToExternalIds')
            ->willReturn([
                1 => 1111,
                2 => 2222,
            ]);
        $OrdersStub->expects($this->exactly(2))
            ->method('changeOrderStatus')
            ->withConsecutive(
                [$this->equalTo(1), $this->equalTo('waiting')],
                [$this->equalTo(2), $this->equalTo('sorted')]
            );

        $ApiControllerStub = $this->createMock(Api\Controller::class);
        $ApiControllerStub->method('action')
            ->willReturn([
                1111 => 'waiting',
                2222 => 'sorted',
            ]);

        // вычисление результата
        $object = new CurlExec($accountIndex, [
            'Orders' => $OrdersStub,
            'ApiController' => $ApiControllerStub,
        ]);
        $result = $object->getStatuses();

        // проверка
        $this->assertEquals($expectedResult, $result);
    }
}
