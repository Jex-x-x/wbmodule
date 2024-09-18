<?php
namespace Wbs24\Wbapi\Api\V3;

use Bitrix\Main\SystemException;
use Bitrix\Main\Localization\Loc;
use Wbs24\Wbapi\Api;

class Warehouses extends Api
{
    public function apiLaunch($data)
    {
        $requestUrl = $this->getRequestUrl('/api/v3/warehouses');

        return $this->createRequest($requestUrl);
    }
}
