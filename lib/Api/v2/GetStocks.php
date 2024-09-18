<?php
namespace Wbs24\Wbapi\Api\V2;

use Bitrix\Main\SystemException;
use Bitrix\Main\Localization\Loc;
use Wbs24\Wbapi\Api;

class GetStocks extends Api
{
    protected $take = 50;

    public function apiLaunch($data)
    {
        $allStocks = [];
        $currentQuantity = 0;
        $skip = $data['skip'] ?? 0;
        $totalQuantity = 0;
        $maxQuantity = $data['max_quantity'] ?? 1000;
        do {
            if ($totalQuantity >= $maxQuantity) break;

            $param = $this->getParam($data, $skip);
            $response = $this->getResponse($param);
            $stocks = $response['stocks'] ?? [];
            $allStocks = array_merge($allStocks, $stocks);
            $currentQuantity = $response['total'] ?? 0;
            $totalQuantity += $currentQuantity;
            if ($currentQuantity == $this->take) {
                $skip += $this->take;
            }
        } while ($currentQuantity == $this->take);

        return $allStocks;
    }

    protected function getParam($data, $skip)
    {
        $param = [
            'skip' => $skip,
            'take' => $this->take,
        ];
        if ($data['search']) $param['search'] = $data['search'];

        return $param;
    }

    protected function getResponse($param)
    {
        $requestUrl = $this->getRequestUrl('/api/v2/stocks');
        $response = $this->createRequest($requestUrl, $param);

        return $response;
    }
}
