<?php
namespace Wbs24\Wbapi;

class AccountsTest extends BitrixTestCase
{
    public function test_getDependencies()
    {
        // входные параметры
        $accountIndex = 2;

        // результат для проверки
        $expectedAccountIndex = $accountIndex;
        $expectedAccountPrefix = 'a2_';

        // объект, использующий тестируемый трейт
        $obj = new class {
            use Accounts;
        };

        // вычисление результата
        $dependences = $obj->getDependencies($accountIndex);

        // проверка
        $this->assertEquals($expectedAccountIndex, $dependences['Option']->getAccountIndex());
        $this->assertEquals($expectedAccountPrefix, $dependences['Option']->getPrefix());
    }
}
