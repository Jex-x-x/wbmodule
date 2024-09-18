<?php
namespace Wbs24\Wbapi\Agents;

use Bitrix\Main\SystemException;
use Wbs24\Wbapi\{
    Api,
    Agents,
    ProductDirectory,
    Product,
    Prices\Helper as PricesHelper,
    Prices\Stack as PricesStack
};

class Prices extends Agents {
    protected $main;
    protected $moduleId;
    protected $wrappers;

    protected $ApiController;
    protected $PricesHelper;
    protected $PricesStock;
    protected $ProductDirectory;
    protected $Product;

    protected $maxQuantityByStep = 1000;
    protected $postfixAgentNames = [
        '::getPricesAgent();',
        '::updatePricesAgent();',
        //'::getAndUpdatePricesAgent();'
    ];
    protected $lockOption = 'agents_prices_lock';
    protected $forceUpdateOption = 'forceUpdate';
    protected $barcodeAsOfferId;

    public function setAccountIndex(int $accountIndex)
    {
        $dependencies = $this->getDependencies($accountIndex);
        $this->setDependences([], $dependencies, $accountIndex);
    }

    public function setDependences(array $objects = [], array $dependencies, int $accountIndex)
    {
        $this->ApiController = $objects['ApiController'] ?? new Api\Controller();
        $this->PricesHelper = $objects['PricesHelper'] ?? new PricesHelper($dependencies);
        $this->PricesStack = $objects['PricesStack'] ?? new PricesStack($accountIndex, $dependencies);
        $this->ProductDirectory = $objects['ProductDirectory'] ?? new ProductDirectory($accountIndex, $dependencies);
        $this->Product = $objects['Product'] ?? new Product($dependencies);
        $this->Stocks = $objects['Stocks'] ?? new Stocks($dependencies);

        $this->barcodeAsOfferId = ($this->wrappers->Option->get($this->moduleId, 'barcodeAsOfferId') == 'Y');
        $this->discount = $this->wrappers->Option->get(
            $this->moduleId,
            'discount'
        );
        $this->forceUpdate = ($this->wrappers->Option->get(
            $this->moduleId,
            $this->forceUpdateOption
        ) == 'Y');
    }

    public function updateAgents(array $accounts = [1])
    {
        foreach ($accounts as $accountIndex) {
            $this->wrappers->Option->setPrefix($accountIndex);
            if (!$this->agentCheckOn()) continue;
            if (empty($this->wrappers->Option->get($this->moduleId, 'apiKey'))) continue;
            if (
                empty($this->wrappers->Option->get($this->moduleId, 'prices_scheduler_interval'))
                || !is_numeric($this->wrappers->Option->get($this->moduleId, 'prices_scheduler_interval'))
            ) continue;

            foreach ($this->postfixAgentNames as $postfixAgentName) {
                if ($accountIndex > 1) {
                    $postfixAgentName = str_replace("()", "(${accountIndex})", $postfixAgentName);
                }

                $this->wrappers->Option->set($this->moduleId, $this->lockOption, 'N');
                $this->updateAgent($postfixAgentName);
            }
        }
    }

    public function updateAgent(string $postfixAgentName) {
        $agentName = $this->getNameAgent($postfixAgentName);
        $interval = intval($this->wrappers->Option->get($this->moduleId, "prices_scheduler_interval"));
        $this->addAgent($agentName, $interval);
    }

    public function getNameAgent(string $postfixAgentName): string
    {
        return "\\".__CLASS__.$postfixAgentName;
    }

    protected function agentCheckOn(): bool
    {
        return $this->wrappers->Option->get($this->moduleId, "prices_scheduler_is_on") == "Y" ? true : false;
    }

    public static function getPricesAgent(int $accountIndex = 1): string
    {
        return ''; // с версии 0.6.7 агент больше не используется
    }

    // устаревшая версия
    public function getPrices(int $accountIndex)
    {
        // очистить старую очередь цен
        $this->PricesStack->clean();

        // получить цены с wb
        $prices = $this->ApiController->action([
            'action' => 'get_prices',
            'account_index' => $accountIndex,
        ]);

        // записать цены в очередь
        $this->PricesStack->add($prices);
    }

    protected function getPricesStepAgent($offset = 0, $accountIndex)
    {
        $agentsPricesObjects = new Prices();
        $agentsPricesObjects->setAccountIndex($accountIndex);

        // получить цены с wb
        $response = $this->ApiController->action([
            'action' => 'get_prices_v2',
            'account_index' => $accountIndex,
            'offset' => $offset,
        ]);

        $prices = $response['result']['prices'];
        $offset = $response['result']['offset'];

        if ($prices && $offset) {
            // записать цены в очередь
            $this->PricesStack->add($prices);

            $agentName = $agentsPricesObjects->getNameAgent('::'.__FUNCTION__.'('.$offset.', '.$accountIndex.');');
            $agentsPricesObjects->addAgent($agentName, 1);

            $agentsPricesObjects->setAgentsLock();
        } else {
            $agentsPricesObjects->unsetAgentsLock();
        }

        return '';
    }

    public static function updatePricesAgent(int $accountIndex = 1): string
    {
        return self::getAndUpdatePricesAgent($accountIndex); // с версии 0.6.7 агент заменен на новый getAndUpdatePricesAgent
    }

    public static function getAndUpdatePricesAgent(int $accountIndex = 1): string
    {
        $agentsPricesObjects = new Prices();
        $agentsPricesObjects->setAccountIndex($accountIndex);
        if (!$agentsPricesObjects->areAgentsLocked()) {
            $stocksUpdateActive = $agentsPricesObjects->Stocks->agentCheckOn();
            if (!$stocksUpdateActive) {
                $agentsPricesObjects->Stocks->updateStocksStepAgentV3(0, '', $accountIndex, 'false');
            }

            self::getAndUpdatePricesStepAgent(0, $accountIndex);
        }

        return '\\'.__CLASS__.'::'.__FUNCTION__.'('.$accountIndex.');';
    }

    public static function getAndUpdatePricesStepAgent($offset = 0, $accountIndex)
    {
        $agentsPricesObjects = new Prices();
        $agentsPricesObjects->setAccountIndex($accountIndex);

        $offset = $agentsPricesObjects->getAndUpdatePricesStep($offset, $accountIndex);
        if ($offset) {
            $agentName = $agentsPricesObjects->getNameAgent('::'.__FUNCTION__.'('.$offset.', '.$accountIndex.');');
            $agentsPricesObjects->addAgent($agentName, 1);

            $agentsPricesObjects->setAgentsLock();
        } else {
            $agentsPricesObjects->unsetAgentsLock();
            $agentsPricesObjects->unsetForceUpdateFlag();
        }

        return '';
    }

    protected function getAndUpdatePricesStep(int $offset = 0, int $accountIndex = 1): int
    {
        $agentsPricesObjects = new Prices();
        $agentsPricesObjects->setAccountIndex($accountIndex);
        // Получить цены
        $response = $this->getPricesV2($accountIndex, $offset);
        $prices = $response['prices'];
        $newOffset = $prices ? (int) $response['offset'] : 0;
        // Обновить цены
        if ($prices) $this->updatePricesV2($accountIndex, $prices);

        return $newOffset;
    }

    protected function getPricesV2($accountIndex, $offset)
    {
        $response = $this->ApiController->action([
            'action' => 'get_prices_v2',
            'account_index' => $accountIndex,
            'offset' => $offset,
        ]);

        $prices = $response['result']['prices'];
        $newOffset = $response['result']['offset'];

        return [
            'prices' => $prices,
            'offset' => $newOffset,
        ];
    }

    protected function updatePricesV2($accountIndex, $prices)
    {
        $nmIdsToPrices = $this->getNmIdsToPrices($prices);
        $nmIds = array_keys($nmIdsToPrices);
        $nmIdsToArticles = $this->getNmIdsToArticles($nmIds);
        $useDiscount = $this->useDiscount($this->discount);
        $forceUpdate = $this->forceUpdate;

        // получить соответсвующие элементам цены из bx
        $newPrices = [];
        foreach ($nmIds as $nmId) {
            $articles = $nmIdsToArticles[$nmId] ?? [];
            if (!$articles) continue;

            foreach ($articles as $article) {
                $productInfo = $this->Product->getProductInfoByOfferId($article);
                $productId = $productInfo['id'] ?? false;
                $ratio = $productInfo['package_ratio_value'] ?? 1;
                if (!$productId || !$ratio) continue;

                $response = $this->PricesHelper->getPricesByProductId($productId, $productInfo);
                $newPrice = $response['price'];
                if ($newPrice <= 0) continue;
                $newPriceWithRatio = $newPrice * $ratio; // цена с учетом коэффициента упаковки
                if (
                    $newPriceWithRatio != $nmIdsToPrices[$nmId]
                    || $forceUpdate === true
                ) {
                    $preparedNewPrices = [
                        'nmId' => $nmId,
                        'price' => $newPriceWithRatio,
                    ];
                    if ($useDiscount) $preparedNewPrices['discount'] = (int) $this->discount;

                    $newPrices[] = $preparedNewPrices;
                }
                break;
            }
        }

        // обновить цены на wb
        if ($newPrices) {
            $response = $this->ApiController->action([
                'action' => 'set_prices_v2',
                'account_index' => $accountIndex,
                'prices' => $newPrices,
            ]);

            $uploadId = $response['result']['uploadId'];
            $error = $response['result']['error'];
            $errorText = $response['result']['errorText'];

            // error processing
            if ($error && $errorText) {
                $this->createReport(
                    'prices_update_error.txt',
                    'upload #'.$uploadId.' is failed. Detail error text: '.$errorText
                );
            }
            // upload prices processing
            if ($uploadId) {
                $response = $this->ApiController->action([
                    'action' => 'get_detail_upload_info',
                    'account_index' => $accountIndex,
                    'uploadId' => $uploadId,
                ]);
                $detailUploadInfo = $response['result'];
                if ($detailUploadInfo) {
                    $nmIdsToErrorText = $this->getErrorNmIdsToErrorText(
                        $detailUploadInfo
                    );
                    if ($nmIdsToErrorText) {
                        $errorMessage = json_encode($nmIdsToErrorText, JSON_UNESCAPED_UNICODE);
                        $this->createReport(
                            'prices_update_error.txt',
                            'upload #'.$uploadId.' has errors: '.$errorMessage
                        );
                    }
                }
            }
        }
    }

    protected function useDiscount($discountValue)
    {
        $useDiscount = false;
        if (
            $discountValue === '0'
            || (int) $discountValue > 0
        ) {
            $useDiscount = true;
        }

        return $useDiscount;
    }

    public static function updatePricesStepAgent(int $skip = 0, int $accountIndex = 1): string
    {
        $agentsPricesObjects = new Prices();
        $agentsPricesObjects->setAccountIndex($accountIndex);

        $nextSkip = $agentsPricesObjects->updatePricesStep($skip, $accountIndex);
        if ($nextSkip) {
            $agentName = $agentsPricesObjects->getNameAgent('::'.__FUNCTION__.'('.$nextSkip.', '.$accountIndex.');');
            $agentsPricesObjects->addAgent($agentName, 1);

            $agentsPricesObjects->setAgentsLock();
        } else {
            $agentsPricesObjects->unsetAgentsLock();
        }

        return '';
    }

    public function updatePricesStep(int $skip = 0, int $accountIndex = 1): int
    {
        $maxQuantity = $this->maxQuantityByStep;

        // получить порцию цен из очереди
        $prices = $this->PricesStack->get($skip, $maxQuantity);

        $nextSkip = $prices ? $skip + $maxQuantity : false;
        if (!$nextSkip) return false;
        $nmIdsToPrices = $this->getNmIdsToPrices($prices);
        $nmIds = array_keys($nmIdsToPrices);
        $nmIdsToArticles = $this->getNmIdsToArticles($nmIds);

        // получить соответсвующие элементам цены из bx
        $newPrices = [];
        foreach ($nmIds as $nmId) {
            $articles = $nmIdsToArticles[$nmId] ?? [];
            if (!$articles) continue;

            foreach ($articles as $article) {
                $productInfo = $this->Product->getProductInfoByOfferId($article);
                $productId = $productInfo['id'] ?? false;
                $ratio = $productInfo['package_ratio_value'] ?? 1;
                if (!$productId || !$ratio) continue;

                $response = $this->PricesHelper->getPricesByProductId($productId, $productInfo);
                $newPrice = $response['price'];
                if ($newPrice <= 0) continue;
                $newPriceWithRatio = $newPrice * $ratio; // цена с учетом коэффициента упаковки
                if ($newPriceWithRatio != $nmIdsToPrices[$nmId]) {
                    $newPrices[] = [
                        'nmId' => $nmId,
                        'price' => $newPriceWithRatio,
                    ];
                }
                break;
            }
        }

        // обновить цены на wb
        if ($newPrices) {
            $response = $this->ApiController->action([
                'action' => 'set_prices_v2',
                'account_index' => $accountIndex,
                'prices' => $newPrices,
            ]);

            $uploadId = $response['uploadId'];
            $error = $response['error'];
            $errorText = $response['errorText'];

            // Обработка ошибок
            if ($error && $errorText) {
                $this->createReport(
                    'prices_update_error.txt',
                    'upload #'.$uploadId.' is failed. Detail error text: '.$errorText
                );
            }

            // Обработка загрузки цен
            if ($uploadId) {
                $detailUploadInfo = $this->ApiController->action([
                    'action' => 'get_detail_upload_info',
                    'account_index' => $accountIndex,
                    'uploadId' => $uploadId,
                ]);

                if ($detailUploadInfo) {
                    $nmIdsToErrorText = $this->getErrorNmIdsToErrorText(
                        $detailUploadInfo
                    );
                    $errorMessage = json_encode($nmIdsToErrorText);
                    $this->createReport(
                        'prices_update_error.txt',
                        'upload #'.$uploadId.' has errors: '.$errorMessage
                    );
                }
            }
        }

        return $nextSkip;
    }

    protected function getErrorNmIdsToErrorText($detailUploadInfo)
    {
        $nmIdsToErrorText = [];

        foreach ($detailUploadInfo as $product) {
            $uploadStatus = $product['status'];
            $nmId = $product['nmID'];
            if (!$nmId) continue;
            switch ((int) $uploadStatus) {
                case 3:
                    $nmIdsToErrorText[] = [
                        'nmId' => $nmId,
                        'errorText' => $product['errorText'] ?? '',
                    ];
                    break;
            }
        }

        return $nmIdsToErrorText;
    }

    protected function getNmIdsToPrices(array $prices): array
    {
        $nmIdsToPrices = [];
        foreach ($prices as $priceInfo) {
            $nmIdsToPrices[$priceInfo['nmId']] = $priceInfo['price'];
        }

        return $nmIdsToPrices;
    }

    protected function getNmIdsToArticles(array $nmIds): array
    {
        $nmIdsToArticles = [];
        $minNmId = min($nmIds) - 1;
        $maxNmId = max($nmIds) + 1;
        $barcodesToProductInfo = $this->ProductDirectory->get(
            [
                '>nm_id' => $minNmId,
                '<nm_id' => $maxNmId,
            ], [
                'order' => 'nm_id',
            ],
            'barcode'
        );

        foreach ($barcodesToProductInfo as $product) {
            $nmId = $product['nmId'] ?? false;
            if (!$nmId) continue;

            $nmIdsToArticles[$nmId][] = $this->barcodeAsOfferId ? $product['barcode'] : $product['article'];
        }

        return $nmIdsToArticles;
    }

    public function setAgentsLock()
    {
        $this->wrappers->Option->set($this->moduleId, $this->lockOption, 'Y');
    }

    public function unsetAgentsLock()
    {
        $this->wrappers->Option->set($this->moduleId, $this->lockOption, 'N');
    }

    public function unsetForceUpdateFlag()
    {
        $this->wrappers->Option->set($this->moduleId, $this->forceUpdateOption, 'N');
    }

    public function areAgentsLocked()
    {
        return ($this->wrappers->Option->get($this->moduleId, $this->lockOption) == 'Y');
    }
}
