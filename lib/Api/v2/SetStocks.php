<?php
namespace Wbs24\Wbapi\Api\V2;

use Bitrix\Main\SystemException;
use Bitrix\Main\Localization\Loc;
use Wbs24\Wbapi\Api;

class SetStocks extends Api
{
    protected $take = 50;

    public function apiLaunch($data)
    {
        /**
         * в $data ожидается массив вида:
         * $data['stocks'] = [
         *   [
         *     'barcode' => 123,
         *     'stock' => 1,
         *     'warehouseId' => 1,
         *   ],
         * ];
         **/
        $requestUrl = $this->getRequestUrl('/api/v2/stocks');
        $result = $this->createRequest($requestUrl, $data['stocks'], [
            'post' => true,
        ]);

        return $result;
    }

    public function processAction($responseData)
    {
        $result = $responseData['result'] ?? [];
        $errorList = $result['data']['error'] ?? [];
        foreach ($errorList as $elem) {
            $msg = 'barcode: '.$elem['barcode'].': '.$elem['err'];
            $this->createReport('error_log.txt', $msg);
        }

        return [
            'result' => $result['error'] ? 'error' : 'success',
        ];
    }
}
