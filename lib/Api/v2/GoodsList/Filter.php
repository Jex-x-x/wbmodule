<?php
namespace Wbs24\Wbapi\Api\V2\GoodsList;

use Bitrix\Main\SystemException;
use Bitrix\Main\Localization\Loc;
use Wbs24\Wbapi\Api;

class Filter extends Api
{
    public function apiLaunch($data)
    {
        $this->limit = 1000;
        $param = $this->getParam($data);
        $response = $this->getResponse($param);

        return $this->getPreapredResponse($response, $data);
    }

    protected function getParam($data)
    {


        $param = [
            'limit' => $this->limit,
            'offset' => $data['offset'],
        ];

        return $param;
    }

    protected function getResponse($param)
    {
        $requestUrl = $this->getRequestUrl('/api/v2/list/goods/filter', 'price');
        $response = $this->createRequest($requestUrl, $param);

        return $response;
    }

    protected function getPreapredResponse($response, $data)
    {
        $productsList = $response['data']['listGoods'];
        if (!$productsList) return [];
        $prices = [];

        foreach ($productsList as $product) {
            $nmId = $product['nmID'];
            $sizes = $product['sizes'];
            $currencyCode = $product['currencyIsoCode4217'];
            $sizePrices = [];
            foreach ($sizes as $size) {
                $sizePrices[] = $size['price'];
            }
            $price = max($sizePrices);

            if (!$nmId) continue;
            if ($currencyCode != 'RUB') continue;

            $prices[] = [
                'nmId' => $nmId,
                'price' => $price,
            ];
        }

        $offset = $data['offset'];
        $offset += $this->limit;

        return [
            'prices' => $prices,
            'offset' => $offset
        ];
    }

    public function processAction($responseData)
    {
        return $responseData;
    }
}
