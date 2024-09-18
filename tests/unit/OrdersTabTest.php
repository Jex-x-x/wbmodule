<?php
namespace Wbs24\Wbapi;

use Bitrix\Main\Loader;

class OrdersTabTest extends BitrixTestCase
{
    public function testUpdateAgents()
    {
        // входные параметры
        $xmlIdsArray = [
            'WB_45499524-0061-1',
            'WB2_45499524-0061-1',
            'WB22_45499524-0061-1',
        ];

        // результат для проверки
        $expectedResultsArray = [
            1,
            2,
            22,
        ];

        // заглушки

        foreach ($xmlIdsArray as $key => $xmlId) {
            // вычисление результата
            // вызов protected метода
            $method = $this->getMethod('Wbs24\\Wbapi\\OrdersTab', 'getAccountIndexByXmlId');
            $object = new OrdersTab();
            $result = $method->invokeArgs($object, [$xmlId]);

            // проверка
            $this->assertEquals($expectedResultsArray[$key], $result);
        }
    }
}
