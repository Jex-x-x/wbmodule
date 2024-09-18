<?php
namespace Wbs24\Wbapi\Api\V3;

use Wbs24\Wbapi\Api;

class SetStocks extends Api
{
    public function apiLaunch($data)
    {
        $warehouseId = $data['warehouse_id'] ?? false;
        if (!is_numeric($warehouseId)) return;

        $requestUrl = $this->getRequestUrl('/api/v3/stocks/'.$warehouseId);

        return $this->createRequest($requestUrl, [
            'stocks' => $data['stocks'],
        ], [
            'put' => true,
        ]);
    }
}
