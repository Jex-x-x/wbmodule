<?php
namespace Wbs24\Wbapi\Api\V2\Upload;

use Bitrix\Main\SystemException;
use Bitrix\Main\Localization\Loc;
use Wbs24\Wbapi\Api;

class Task extends Api
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
            'data' => $data['prices']
        ];

        return $param;
    }

    protected function getResponse($param)
    {
        $requestUrl = $this->getRequestUrl('/api/v2/upload/task', 'price');
        $response = $this->createRequest($requestUrl, $param, [
            'post' => true,
        ]);

        return $response;
    }

    protected function getPreapredResponse($response, $data)
    {
        $uploadId = $response['data']['id'] ?? false;
        $error = $response['data']['error'] ?? '';
        $errorText = $response['data']['errorText'] ?? '';

        return [
            'uploadId' => $uploadId,
            'error' => $error,
            'errorText' => $errorText
        ];
    }

    public function processAction($responseData)
    {
        return $responseData;
    }
}
