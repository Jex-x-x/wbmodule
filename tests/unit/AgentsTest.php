<?php
namespace Wbs24\Wbapi;

use Bitrix\Main\Loader;

class AgentsTest extends BitrixTestCase
{
    public function testUpdateAgents()
    {
        // входные параметры
        $accounts = [2];
        $quantityOfAgents = 4;

        // результат для проверки
        $quantityAgents = count($accounts) * $quantityOfAgents;

        // заглушки
        $OptionStub = $this->createMock(Wrappers\Option::class);
        $OptionStub->method('get')
            ->will($this->returnCallback(
                function ($moduleId, $option) {
                    return ($option == 'scheduler_is_on') ? 'Y' : '1';
                }
            ));
        $OptionStub->method('set')
            ->willReturn(null);

        $CAgentStub = $this->createMock(Wrappers\CAgent::class);
        $CAgentStub->method('RemoveModuleAgents')
            ->willReturn(null);
        $CAgentStub->method('AddAgent')
            ->willReturn(null);

        // проверка
        $CAgentStub->expects($this->exactly($quantityAgents))
            ->method('AddAgent')
            ->with(
                $this->stringContains('2)'),
                $this->anything(),
                $this->anything(),
                $this->anything()
            );

        // вычисление результата
        $object = new Agents([
            'Option' => $OptionStub,
            'CAgent' => $CAgentStub,
        ]);
        $result = $object->updateAgents($accounts);
    }
}
