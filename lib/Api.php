<?php
namespace Wbs24\Wbapi;

use Bitrix\Main\SystemException;

class Api {
    use Exception; // trait

    protected $main;
    protected $moduleId;
    protected $wrappers;
    protected $debug = false;

    protected $accountIndex;

    protected $requestBase;
    protected $requestAllOrders;
    protected $requestOrderStatus;

    protected $numberOfDaysToProcessOrders;
    protected $isHttps;
    protected $testMode;
    protected $saveApiLog;
    protected $param;
    protected $limit;
    protected $offset;

    public function __construct($objects = [])
    {
        $this->main = $objects['Main'] ?? new Main();
        $this->moduleId = $this->main->getModuleId();
        $this->wrappers = new Wrappers($objects);

        $this->accountIndex = $this->wrappers->Option->getAccountIndex();

        $this->numberOfDaysToProcessOrders = $this->wrappers->Option->get($this->moduleId, 'numberOfDaysToProcessOrders');
        $this->isHttps = $this->wrappers->Option->get($this->moduleId, 'isHttps');
        $this->testMode = $this->wrappers->Option->get($this->moduleId, 'testMode');
        $this->saveApiLog = $this->wrappers->Option->get($this->moduleId, 'saveApiLog');
        if ($this->saveApiLog == 'Y') $this->debug = true;

        // изначальные настройки лимита на получение товаров и позиции смещения
        $this->limit = 50;
        $this->offset = 0;
    }

    protected function getRequestUrl($apiMethod, $urlType = '')
    {
        return $this->getRequestBase($urlType).$apiMethod.$this->getExtOfRequestUrl();
    }

    protected function getRequestBase($urlType = '')
    {
        $baseUrl = 'https://suppliers-api.wildberries.ru';
        if ($urlType) {
            switch ($urlType) {
                case 'price':
                    $baseUrl = 'https://discounts-prices-api.wb.ru';
                    break;
            }
        }

        if ($this->testMode == 'Y') {
            $domain = $this->wrappers->Option->get('main', 'server_name');
            $baseUrl = 'http'.($this->isHttps == 'Y' ? 's' : '').'://'.$domain.'/bitrix/tools/'.$this->moduleId.'/test';
        }

        return $baseUrl;
    }

    protected function getExtOfRequestUrl()
    {
        return ($this->testMode == 'Y') ? '.php' : '';
    }

    protected function getPeriod($days = 1)
    {
       return date('Y-m-d', strtotime('-'.$days.' days')).'T00:00:00Z';
    }

    public function getTimestamp($daysAgo = 0)
    {
        return time() - ($daysAgo * 86400);
    }


    protected function createRequest($url, $data = [], $requestDetail = [])
    {
        try {
            $apiKey = trim($this->wrappers->Option->get($this->moduleId, 'apiKey'));
            if (!$apiKey) return;
            //if (empty($data)) return;
            $isPost = $requestDetail['post'] ?: false;
            $isPut = $requestDetail['put'] ?: false;
            $isGetPdf = $requestDetail['get_pdf'] ?: false;
            $requestType = 'GET';
            if ($isPost) $requestType = 'POST';
            if ($isPut) $requestType = 'PUT';

            $headers = [
                'Authorization: ' . $apiKey,
            ];

            if ($isPost || $isPut) {
                $headers[] = 'Content-Type: application/json';
                $request = json_encode($data);
                $curl = $this->wrappers->Curl->curl_init($url);
            } else {
                $request = $data ? '?'.http_build_query($data) : '';
                $curl = $this->wrappers->Curl->curl_init($url.$request);
            }

            if ($this->debug) $this->createReport('api_log.txt', $requestType.' request to '.$url.' (account '.$this->accountIndex.'): '.$request);

            $this->wrappers->Curl->curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            $this->wrappers->Curl->curl_setopt($curl, CURLOPT_HEADER, false);
            if ($isPost) $this->wrappers->Curl->curl_setopt($curl, CURLOPT_POST, true);
            if ($isPost || $isPut) $this->wrappers->Curl->curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
            $this->wrappers->Curl->curl_setopt($curl, CURLOPT_TIMEOUT, 30);
            $this->wrappers->Curl->curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            if ($isPut) $this->wrappers->Curl->curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
            $this->wrappers->Curl->curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $response = $this->wrappers->Curl->curl_exec($curl);
            $error = $this->wrappers->Curl->curl_error($curl);
            $this->wrappers->Curl->curl_close($curl);
            if ($error) {
                $result = 'cURL Error: ' . $error;
            } else {
                if ($isGetPdf) {
                    $json = json_decode($response, true);
                    if (empty($json['message'])) {
                        $result = $response;
                        $log = 'PDF file';
                    } else {
                        $log = $response;
                    }
                } else {
                    $result = json_decode($response, true);
                    $log = $response;
                }

                if ($this->debug) $this->createReport('api_log.txt', 'response: '.$log);
            }
        } catch (SystemException $exception) {
            $this->exceptionHandler($exception);
        }

        return $result;
    }

    public function processAction($responseData)
    {
        $result = $responseData['result'] ?? [];

        return $result;
    }
}
