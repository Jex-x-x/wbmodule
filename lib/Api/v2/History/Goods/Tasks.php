<?php
namespace Wbs24\Wbapi\Api\V2\History\Goods;

use Bitrix\Main\SystemException;
use Bitrix\Main\Localization\Loc;
use Wbs24\Wbapi\Api;

class Tasks extends Api
{
    public function apiLaunch($data)
    {
        $this->limit = 1000;
        $param = $this->getParam($data);
        $response = $this->getResponse($param);

        return $this->getPreapredResponse($response, $data);
    }

    protected function getParam($data)
    {
        $param = [
            'uploadID' => $data['uploadId'],
            'limit' => $this->limit,
        ];

        return $param;
    }

    protected function getResponse($param)
    {
        $requestUrl = $this->getRequestUrl('/api/v2/history/goods/task', 'price');
        $response = $this->createRequest($requestUrl, $param);

        return $response;
    }

    protected function getPreapredResponse($response, $data)
    {
        $reportProducts = $response['data']['historyGoods'] ?? [];

        return $reportProducts;
    }

    public function processAction($responseData)
    {
        return $responseData;
    }
}
