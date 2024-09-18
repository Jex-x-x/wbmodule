<?php
namespace Wbs24\Wbapi;

use Bitrix\Main\SystemException;
/**
 * абстрактный класс для всех контроллеров
 */
class Controller implements Interfaces\Controller
{
    public function getAllowedActions(): array
    {
        return [];
    }

    public function process(array $param): array
    {
        $action = $param['action'] ?? false;

        if (
            !in_array($action, $this->getAllowedActions())
            || !method_exists($this, $action)
        ) {
            return [
                'success' => 'N',
                'error' => 'Action not found',
                'errorCode' => 'ACTION_NOT_FOUND',
            ];
        }

        return $this->$action($param);
    }
}
