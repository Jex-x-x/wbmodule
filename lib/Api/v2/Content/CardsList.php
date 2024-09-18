<?php
namespace Wbs24\Wbapi\Api\V2\Content;

use Wbs24\Wbapi\Api;

class CardsList extends Api
{
    public function apiLaunch($data)
    {
        $requestUrl = $this->getRequestUrl('/content/v2/get/cards/list');
        $limit = $data['limit'] ?: 1000;
        $nmId = $data['last_nm_id'] ?? false;
        $updatedAt = $data['last_updated_at'] ?? false;

        $param = [
            'settings' => [
                'cursor' => [
                    'limit' => $limit,
                ],
                'filter' => [
                    'withPhoto' => -1,
                ],
            ],
        ];
        if ($nmId && $updatedAt) {
            $param['settings']['cursor']['nmId'] = $nmId;
            $param['settings']['cursor']['updatedAt'] = $updatedAt;
        }

        return $this->createRequest($requestUrl, $param, [
            'post' => true,
        ]);
    }
}
