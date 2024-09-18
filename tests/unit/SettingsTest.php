<?php
namespace Wbs24\Wbapi;

use Bitrix\Main\Loader;

class SettingsTest extends BitrixTestCase
{
    public function testGetAccounts()
    {
        // входные параметры
        $optionsResultsArray = [
            '1,3,4',
            '',
        ];

        // результат для проверки
        $expectedResultsArray = [
            [1, 3, 4],
            [1],
        ];

        // массив проверок
        foreach ($optionsResultsArray as $key => $optionsResults) {
            // заглушки
            $OptionStub = $this->createMock(Wrappers\Option::class);
            $OptionStub->method('get')
                ->willReturn($optionsResults);

            // вычисление результата
            $object = new Settings([
                'Option' => $OptionStub,
            ]);
            $result = $object->getAccounts();

            // проверка
            $expectedResult = $expectedResultsArray[$key];
            $this->assertEquals($expectedResult, $result);
        }
    }

    public function testAddAccount()
    {
        // входные параметры
        $optionValue = '1,3,4';

        // результат для проверки
        $expectedSetOptionValue = '1,3,4,5';
        $expectedResult = 5;

        // заглушки
        $OptionStub = $this->createMock(Wrappers\Option::class);
        $OptionStub->method('get')
            ->willReturn($optionValue);
        $OptionStub->method('set')
            ->willReturn(null);

        // проверка
        $OptionStub->expects($this->once())
            ->method('set')
            ->with(
                $this->equalTo($this->getModuleId()),
                $this->equalTo('_accounts'),
                $this->equalTo($expectedSetOptionValue)
            );

        // вычисление результата
        $object = new Settings([
            'Option' => $OptionStub,
        ]);
        $result = $object->addAccount();

        // проверка 2
        $this->assertEquals($expectedResult, $result);
    }

    public function testDeleteAccount()
    {
        // входные параметры
        $deleteIndex = 3;
        $optionValue = '1,3,4,5';

        // результат для проверки
        $expectedSetOptionValue = '1,4,5';

        // заглушки
        $OptionStub = $this->createMock(Wrappers\Option::class);
        $OptionStub->method('get')
            ->willReturn($optionValue);
        $OptionStub->method('set')
            ->willReturn(null);

        // проверка
        $OptionStub->expects($this->once())
            ->method('set')
            ->with(
                $this->equalTo($this->getModuleId()),
                $this->equalTo('_accounts'),
                $this->equalTo($expectedSetOptionValue)
            );

        // вычисление результата
        $object = new Settings([
            'Option' => $OptionStub,
        ]);
        $result = $object->deleteAccount($deleteIndex);
    }
}
