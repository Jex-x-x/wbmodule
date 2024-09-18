<?php
namespace Wbs24\Wbapi\Api\V2;

use Bitrix\Main\SystemException;
use Bitrix\Main\Localization\Loc;
use Wbs24\Wbapi\Api;

class Warehouses extends Api
{
    public function apiLaunch($data)
    {
        $requestUrl = $this->getRequestUrl('/api/v2/warehouses');

        return $this->createRequest($requestUrl);
    }
}
