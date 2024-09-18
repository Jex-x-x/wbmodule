<?php
namespace Wbs24\Wbapi\Api;

use Bitrix\Main\SystemException;
use Wbs24\Wbapi\Api\Fbs;
use Wbs24\Wbapi\{
    Exception,
    Accounts
};

class Controller
{
    use Exception; // trait
    use Accounts; // trait

    public function action($data)
    {
        try {
            $action = $data['action'] ?? false;
            $object = false;
            $result = 'error';

            $accountIndex = $data['account_index'] ?? 1;
            $dependencies = $this->getDependencies($accountIndex);

            switch ($action) {
                case 'get_orders':
                    $object = new V3\Orders($dependencies);
                    break;
                case 'get_statuses':
                    $object = new V3\Orders\Status($dependencies);
                    break;

                // не используется - API v2 отключено
                case 'get_stocks':
                    $object = new V2\GetStocks($dependencies);
                    break;
                case 'set_stocks':
                    $object = new V2\SetStocks($dependencies);
                    break;

                case 'get_nomenclature':
                    //$object = new V1\Content\CardsCursorList($dependencies);
                    $object = new V2\Content\CardsList($dependencies);
                    break;
                case 'get_warehouses':
                    $object = new V3\Warehouses($dependencies);
                    break;
                case 'get_stocks_v3':
                    $object = new V3\GetStocks($dependencies);
                    break;
                case 'set_stocks_v3':
                    $object = new V3\SetStocks($dependencies);
                    break;

                // не используется - API v1 отключено
                case 'get_prices':
                    $object = new V1\Info($dependencies);
                    break;

                case 'get_prices_v2':
                    $object = new V2\GoodsList\Filter($dependencies);
                    break;

                // не используется - API v1 отключено
                case 'set_prices':
                    $object = new V1\Prices($dependencies);
                    break;

                case 'set_prices_v2':
                    $object = new V2\Upload\Task($dependencies);
                    break;
                case 'get_upload_status': // не используется
                    $object = new V2\History\Tasks($dependencies);
                    break;
                case 'get_detail_upload_info':
                    $object = new V2\History\Goods\Tasks($dependencies);
                    break;
            }

            if ($object) {
                $result = $object->apiLaunch($data);
            }

            return $this->processAction($object, [
                'result' => $result,
            ]);
        } catch (SystemException $exception) {
            $this->exceptionHandler($exception);
        }
    }

    protected function processAction($object, $responseData)
    {
        $processResult = [
            'result' => 'error',
        ];

        if ($object) {
            $processResult = $object->processAction($responseData);
        }

        return $processResult;
    }
}
