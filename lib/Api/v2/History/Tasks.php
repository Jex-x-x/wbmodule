<?php
namespace Wbs24\Wbapi\Api\V2\History;

use Bitrix\Main\SystemException;
use Bitrix\Main\Localization\Loc;
use Wbs24\Wbapi\Api;

class Tasks extends Api
{
    public function apiLaunch($data)
    {
        $param = $this->getParam($data);
        $response = $this->getResponse($param);

        return $this->getPreapredResponse($response, $data);
    }

    protected function getParam($data)
    {
        $param = [
            'uploadID' => (int) $data['uploadId']
        ];

        return $param;
    }

    protected function getResponse($param)
    {
        $requestUrl = $this->getRequestUrl('/api/v2/history/tasks', 'price');
        $response = $this->createRequest($requestUrl, $param);

        return $response;
    }

    protected function getPreapredResponse($response, $data)
    {
        $uploadStatus = $response['data']['status'] ?? false;

        return $uploadStatus;
    }

    public function processAction($responseData)
    {
        return $responseData;
    }
}
