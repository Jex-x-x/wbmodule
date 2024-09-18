<?php
namespace Wbs24\Wbapi;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class UpdateTest extends BitrixTestCase
{
    public function testGetUpdateMessage()
    {
        // входные параметры
        Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.$this->getModuleId().'/lib/Update.php');
        $lastVersion = '2.0.0';
        $moduleId = $this->getModuleId();
        $suffix = strtoupper($moduleId);
        $updateList = [
            'MODULE' => [
                [
                    '@' => [
                        'ID' => 'wbs24.test',
                    ],
                    '#' => [
                        'VERSION' => [
                            [
                                '@' => [
                                    'ID' => '1.0.0',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    '@' => [
                        'ID' => $moduleId,
                    ],
                    '#' => [
                        'VERSION' => [
                            [
                                '@' => [
                                    'ID' => '1.5.0',
                                ],
                            ],
                            [
                                '@' => [
                                    'ID' => $lastVersion,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // результат для проверки
        $expectedResult =
            Loc::getMessage($suffix.".UPDATE_MESSAGE")." ".
            $lastVersion.", ".
            '<a href="/bitrix/admin/update_system_partner.php?tabControl_active_tab=tab2&addmodule='.$moduleId.'">'.
                Loc::getMessage($suffix.".UPDATE_RUN").
            '</a>'
        ;

        // заглушка
        $CUpdateClientPartnerStub = $this->createMock(Wrappers\CUpdateClientPartner::class);
        $CUpdateClientPartnerStub->method('GetUpdatesList')
            ->willReturn($updateList);

        // вычисление результата
        $update = new Update([
            'CUpdateClientPartner' => $CUpdateClientPartnerStub,
        ]);
        $result = $update->getUpdateMessage($moduleId);

        // проверка
        $this->assertEquals($expectedResult, $result);
    }
}
