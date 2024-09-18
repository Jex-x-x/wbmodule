<?php
namespace Wbs24\Wbapi\Api\V1;

use Wbs24\Wbapi\Api;

class Prices extends Api
{
    public function apiLaunch($data)
    {
        $requestUrl = $this->getRequestUrl('/public/api/v1/prices');

        return $this->createRequest($requestUrl, $data['prices'], [
            'post' => true,
        ]);
    }
}
