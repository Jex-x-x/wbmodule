<?php
namespace Wbs24\Wbapi\Agents;

use Bitrix\Main\SystemException;
use Wbs24\Wbapi\{
    Api,
    Agents,
    Product,
    ProductStocks,
    ProductDirectory,
    Wrappers
};

class Stocks extends Agents {
    protected $main;
    protected $moduleId;
    protected $wrappers;

    protected $ApiController;
    protected $ProductStocks;
    protected $ProductDirectory;

    protected $maxQuantityByStep = 100;
    protected $postfixAgentNames = [
        '::updateStocksAgentV3();',
    ];
    protected $allowUpdateStocks = true;

    public function setDependences($objects = [], $dependencies, $accountIndex)
    {
        $this->ApiController = $objects['ApiController'] ?? new Api\Controller();
        $this->ProductStocks = $objects['ProductStocks'] ?? new ProductStocks($dependencies);
        $this->ProductDirectory = $objects['ProductDirectory'] ?? new ProductDirectory($accountIndex, $dependencies);
    }

    public function updateAgents(array $accounts = [1])
    {
        foreach ($accounts as $accountIndex) {
            $this->wrappers->Option->setPrefix($accountIndex);
            if (!$this->agentCheckOn()) continue;
            if (empty($this->wrappers->Option->get($this->moduleId, 'apiKey'))) continue;
            if (
                empty($this->wrappers->Option->get($this->moduleId, 'stocks_scheduler_interval'))
                || !is_numeric($this->wrappers->Option->get($this->moduleId, 'stocks_scheduler_interval'))
            ) continue;

            foreach ($this->postfixAgentNames as $postfixAgentName) {
                if ($accountIndex > 1) {
                    $postfixAgentName = str_replace("()", "(${accountIndex})", $postfixAgentName);
                }

                $this->updateAgent($postfixAgentName);
            }
        }
    }

    public function updateAgent(string $postfixAgentName)
    {
        $agentName = $this->getNameAgent($postfixAgentName);
        $interval = intval($this->wrappers->Option->get($this->moduleId, "stocks_scheduler_interval"));
        $this->addAgent($agentName, $interval);
    }

    public function getNameAgent(string $postfixAgentName): string
    {
        return "\\".__CLASS__.$postfixAgentName;
    }

    protected function agentCheckOn(): bool
    {
        return $this->wrappers->Option->get($this->moduleId, "stocks_scheduler_is_on") == "Y" ? true : false;
    }

    // не используется - API v2 отключено
    public static function updateStocksAgent($accountIndex = 1)
    {
        self::updateStocksStepAgent(0, $accountIndex);

        return '\\'.__CLASS__.'::'.__FUNCTION__.'('.$accountIndex.');';
    }

    // не используется - API v2 отключено
    public static function updateStocksStepAgent($skip = 0, $accountIndex = 1)
    {
        $agentsStocksObjects = new Stocks();
        $dependencies = $agentsStocksObjects->getDependencies($accountIndex);
        $agentsStocksObjects->setDependences([], $dependencies, $accountIndex);

        $nextSkip = $agentsStocksObjects->updateStocksStepV3($skip, $accountIndex);
        if ($nextSkip) {
            $agentName = $agentsStocksObjects->getNameAgent('::'.__FUNCTION__.'('.$nextSkip.', '.$accountIndex.');');
            $agentsStocksObjects->addAgent($agentName, 1);
        }

        return '';
    }

    // не используется - API v2 отключено
    public function updateStocksStep($skip = 0, $accountIndex = 1)
    {
        $maxQuantity = $this->maxQuantityByStep;
        $nextSkip = false;

        $stocks = $this->ApiController->action([
            'action' => 'get_stocks',
            'account_index' => $accountIndex,
            'skip' => $skip,
            'max_quantity' => $maxQuantity,
        ]);
        $countStocks = count($stocks);
        if ($countStocks >= $maxQuantity) $nextSkip = $skip + $countStocks;

        // запись в собственный справочник товаров (для нужд других агентов)
        $this->ProductDirectory->add($stocks);

        $stocksForSend = $this->ProductStocks->getStocks($stocks);

        if ($stocksForSend) {
            $this->ApiController->action([
                'action' => 'set_stocks',
                'account_index' => $accountIndex,
                'stocks' => $stocksForSend,
            ]);
        }

        return $nextSkip;
    }

    public static function updateStocksAgentV3($accountIndex = 1)
    {
        self::updateStocksStepAgentV3(0, '', $accountIndex, $allowUpdate = 'true');

        return '\\'.__CLASS__.'::'.__FUNCTION__.'('.$accountIndex.');';
    }

    public static function updateStocksStepAgentV3($lastNmId = 0, $lastUpdatedAt = '', $accountIndex = 1, $allowUpdate = 'true')
    {
        $agentsStocksObjects = new Stocks();
        $dependencies = $agentsStocksObjects->getDependencies($accountIndex);
        $agentsStocksObjects->setDependences([], $dependencies, $accountIndex);

        if ($allowUpdate == 'false') {
            $agentsStocksObjects->allowUpdateStocks = false;
        }

        $nextStepInfo = $agentsStocksObjects->updateStocksStepV3($lastNmId, $lastUpdatedAt, $accountIndex);
        $nextNmId = $nextStepInfo['nmId'] ?? 0;
        $nextUpdatedAt = $nextStepInfo['updatedAt'] ?? '';
        if ($nextNmId && $nextUpdatedAt) {
            $agentName = $agentsStocksObjects->getNameAgent('::'.__FUNCTION__.'('.$nextNmId.', "'.$nextUpdatedAt.'", '.$accountIndex.', "'.$allowUpdate.'");');
            $agentsStocksObjects->addAgent($agentName, 1);
        }

        return '';
    }

    public function updateStocksStepV3($lastNmId = 0, $lastUpdatedAt = '', $accountIndex = 1)
    {
        $maxQuantity = $this->maxQuantityByStep;

        // получить порцию номенклатуры c wb
        $nomenclature = $this->ApiController->action([
            'action' => 'get_nomenclature',
            'account_index' => $accountIndex,
            'last_nm_id' => $lastNmId,
            'last_updated_at' => $lastUpdatedAt,
            'limit' => $maxQuantity,
        ]);
        $nextStepInfo = $this->getNextStepInfoFromNomenclature($nomenclature, $maxQuantity);
        $barcodes = $this->getBarcodesFromNomenclature($nomenclature);
        $products = $this->prepareProductsForProductDirectory($nomenclature);

        // запись в собственный справочник товаров (для нужд других агентов)
        $this->ProductDirectory->add($products);

        if ($this->allowUpdateStocks) $this->updateStocks($accountIndex, $barcodes);

        return $nextStepInfo;
    }

    protected function updateStocks($accountIndex, $barcodes)
    {
        // получить список складов c wb
        // (пока что работаем только с одним складом, возможно указать id склада в настройках модуля)
        $warehouses = $this->ApiController->action([
            'action' => 'get_warehouses',
            'account_index' => $accountIndex,
        ]);
        $warehouseId = $this->getCurrentWarehouseId($warehouses);
        if (!$warehouseId) return;

        // запросить остатки по складу с wb (передав баркоды)
        $stocks = $this->ApiController->action([
            'action' => 'get_stocks_v3',
            'warehouse_id' => $warehouseId,
            'account_index' => $accountIndex,
            'barcodes' => $barcodes,
        ]);
        $preparedStocks = $this->prepareStocksForProductStocks($stocks, $warehouseId, $barcodes);

        // проверка на наличие таких остатков на сайте (пока только из доступного количества)
        $stocksForSend = $this->ProductStocks->getStocks($preparedStocks);
        $preparedStocksForSend = $this->prepareStocksForSend($stocksForSend);

        // не сбрасывать остатки если товары найдены в связанных профилях
        $linkedAccounts = $this->wrappers->Option->get($this->moduleId, 'linked_accounts');
        $preparedStocksForSend = $this->dontDropStocksForLinkedAccounts($linkedAccounts, $preparedStocksForSend);

        // отправить изменения на wb
        if ($preparedStocksForSend) {
            $this->ApiController->action([
                'action' => 'set_stocks_v3',
                'warehouse_id' => $warehouseId,
                'account_index' => $accountIndex,
                'stocks' => $preparedStocksForSend,
            ]);
        }
    }

    protected function getNextStepInfoFromNomenclature($nomenclature, $limit)
    {
        $lastUpdatedAt = $nomenclature['cursor']['updatedAt'] ?? '';
        $lastNmId = $nomenclature['cursor']['nmID'] ?? 0;
        $total = $nomenclature['cursor']['total'] ?? 0;

        $nextStepInfo = [
            'updatedAt' => $lastUpdatedAt,
            'nmId' => $lastNmId,
        ];
        if ($total < $limit) $nextStepInfo = [];

        return $nextStepInfo;
    }

    protected function getBarcodesFromNomenclature($nomenclature)
    {
        $barcodes = [];
        $cards = $nomenclature['cards'] ?? [];

        foreach ($cards as $card) {
            $productBarcodes = $this->getBarcodesFromCard($card);
            foreach ($productBarcodes as $barcode) $barcodes[] = $barcode;
        }

        return $barcodes;
    }

    protected function getBarcodesFromCard($card)
    {
        $barcodes = [];
        $sizes = $card['sizes'] ?? [];

        foreach ($sizes as $size) {
            $skus = $size['skus'] ?? [];
            foreach ($skus as $barcode) {
                // добавляются все штрихкоды (даже если их у товара несколько)
                $barcodes[] = $barcode;
            }
        }

        return $barcodes;
    }

    protected function prepareProductsForProductDirectory($nomenclature)
    {
        $products = [];
        $cards = $nomenclature['cards'] ?? [];

        foreach ($cards as $card) {
            $productBarcodes = $this->getBarcodesFromCard($card);
            foreach ($productBarcodes as $barcode) {
                if (!$barcode) continue;

                $products[] = [
                    'barcode' => $barcode,
                    'article' => $card['vendorCode'],
                    'chrtId' => '',
                    'nmId' => $card['nmID'],
                ];
            }
        }

        return $products;
    }

    protected function getCurrentWarehouseId($warehouses)
    {
        $warehouseId = false;
        $selectedWarehouseId = $this->wrappers->Option->get($this->moduleId, 'warehouse_id');

        foreach ($warehouses as $warehouse) {
            $warehouseId = $warehouse['id'] ?? false;
            if (!$selectedWarehouseId || $warehouseId == $selectedWarehouseId) {
                break;
            }
        }

        return $warehouseId;
    }

    protected function prepareStocksForProductStocks($stocks, $warehouseId, $barcodes)
    {
        $preapredStocks = [];

        foreach ($stocks['stocks'] as $stock) {
            $preapredStocks[] = [
                'barcode' => $stock['sku'],
                'article' => '',
                'stock' => $stock['amount'],
                'warehouseId' => $warehouseId,
            ];
        }

        foreach ($barcodes as $barcode) {
            $foundFlag = false;
            foreach ($preapredStocks as $preapredStock) {
                if ($preapredStock['barcode'] == $barcode) {
                    $foundFlag = true;
                    break;
                }
            }

            $ignoreProductsOutOfStock = ($this->wrappers->Option->get(
                $this->moduleId,
                'ignoreProductsOutOfStock'
            ) == 'Y');
            if (
                !$foundFlag
                && !$ignoreProductsOutOfStock
            ) {
                $preapredStocks[] = [
                    'barcode' => $barcode,
                    'article' => '',
                    'stock' => 0,
                    'warehouseId' => $warehouseId,
                ];
            }
        }

        return $preapredStocks;
    }

    protected function prepareStocksForSend($stocksForSend)
    {
        $preparedStocksForSend = [];

        foreach ($stocksForSend as $stock) {
            $preparedStocksForSend[] = [
                'sku' => $stock['barcode'],
                'amount' => $stock['stock'],
            ];
        }

        return $preparedStocksForSend;
    }

    protected function dontDropStocksForLinkedAccounts($linkedAccounts, $preparedStocksForSend)
    {
        $accountIds = explode(',', $linkedAccounts);

        // обход связанных аккаунтов
        foreach ($accountIds as $id) {
            if (!is_numeric($id)) continue;

            $optionObj = new Wrappers\Option();
            $optionObj->setPrefix($id);
            $productObj = new Product(['Option' => $optionObj]);

            // обход подготовленных остатков
            foreach ($preparedStocksForSend as $k => $stock) {
                ['sku' => $barcode, 'amount' => $amount] = $stock;
                // если остаток = 0, то проверить нет ли товара с этим ШК для текущего аккаунта
                if ($amount == 0 && $barcode) {
                    $productId = $productObj->getProductIdByOfferId($barcode);
                    // если есть, то удалить его из подготовленных остатков
                    if ($productId) unset($preparedStocksForSend[$k]);
                }
            }
        }

        // вернуть сокращенный список подготовленных остатков
        return $preparedStocksForSend;
    }
}
