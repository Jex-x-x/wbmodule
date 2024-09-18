<?php
namespace Wbs24\Wbapi\Api\V1;

use Wbs24\Wbapi\Api;

class Info extends Api
{
    public function apiLaunch($data)
    {
        $requestUrl = $this->getRequestUrl('/public/api/v1/info');

        return $this->createRequest($requestUrl, [
            'quantity' => 0,
        ]);
    }
}
