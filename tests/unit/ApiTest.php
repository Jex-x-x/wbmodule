<?php
namespace Wbs24\Wbapi;

use Bitrix\Main\Loader;

class ApiTest extends BitrixTestCase
{
    public function testCreateRequest()
    {
        // входные параметры
        $url = 'test.php';
        $data = [
            'posting_number' => 1,
        ];
        $accountIndex = 2;

        // результат для проверки
        $jsonForExpectedResult = '{"test":"msg"}';
        $expectedResult = [
            'test' => 'msg',
        ];

        // заглушки
        $OptionStub = $this->createMock(Wrappers\Option::class);
        $OptionStub->method('getAccountIndex')
            ->willReturn($accountIndex);
        $OptionStub->method('get')
            ->willReturn('1');

        $CurlStub = $this->createMock(Wrappers\Curl::class);
        $CurlStub->method('curl_init')
            ->willReturn(\stdClass::class);
        $CurlStub->method('curl_setopt')
            ->willReturn(null);
        $CurlStub->method('curl_error')
            ->willReturn(false);
        $CurlStub->method('curl_close')
            ->willReturn(null);
        $CurlStub->method('curl_exec')
            ->willReturn($jsonForExpectedResult);

        // вычисление результата
        // вызов protected метода
        $method = $this->getMethod('Wbs24\\Wbapi\\Api', 'createRequest');
        $object = new Api([
            'Option' => $OptionStub,
            'Curl' => $CurlStub,
        ]);
        $result = $method->invokeArgs($object, [$url, $data]);

        // проверка
        $this->assertEquals($expectedResult, $result);
    }
}
