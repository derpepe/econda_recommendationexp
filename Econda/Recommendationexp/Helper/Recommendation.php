<?php

namespace Econda\Recommendationexp\Helper;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;

class Recommendation extends \Magento\Framework\App\Helper\AbstractHelper
{
    const CONFIG_EXPORT = 'recommendationexp/settings/export';
    const CONFIG_REMOTE = 'recommendationexp/settings/remote';

    protected $_productCollectionFactory;
    protected $_categoryCollectionFactory;
    protected $_categoryFactory;
    protected $_storeManager;
    protected $_stockStateInterface;
    protected $_scopeConfig;

    public function __construct(
        Context $context,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\CatalogInventory\Api\StockStateInterface $stockStateInterface,
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->_categoryFactory = $categoryFactory;
        $this->_storeManager = $storeManager;
        $this->_stockStateInterface = $stockStateInterface;
        $this->_scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function isEnabled($storeId) {
        return (bool)$this->_scopeConfig->getValue(self::CONFIG_EXPORT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return string
     */
    public function getRemote($storeId) {
        return $this->_scopeConfig->getValue(self::CONFIG_REMOTE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getProducts($storeId) {
        return $this->_productCollectionFactory
            ->create()
            ->addStoreFilter($storeId)
            ->addFieldToFilter("status", 1)
            ->addAttributeToSelect("*");
    }

    /**
     * @return \Magento\Catalog\Model\ResourceModel\Category\Collection
     */
    public function getCategories() {
        return $this->_categoryCollectionFactory
            ->create()
            ->addAttributeToSelect("*");
    }

    /**
     * @param Product $product
     * @param string $storeId
     * @return string
     */
    public function getProductCsv($product, $storeId)
    {
        $separator = "|";
        $csv = "";
        $csv .= trim($this->getProductId($product, $storeId)).$separator;
        $csv .= trim($this->getProductName($product)).$separator;
        $csv .= trim($this->getProductDescription($product)).$separator;
        $csv .= trim($product->getProductUrl()).$separator;
        $csv .= trim($this->getProductImage($product)).$separator;
        $csv .= trim($this->getProductPrice($product)).$separator;
        $csv .= trim($this->getProductPriceOld($product)).$separator;
        $csv .= trim($this->getProductNew($product)).$separator;
        $csv .= trim($this->getProductQty($product)).$separator;
        $csv .= trim($product->getSku()).$separator;
        $csv .= trim($this->getProductBrand($product)).$separator;
        $csv .= trim($this->getProductCategoriesCsv($product));
        return $csv;
    }

    /**
     * @return string
     */
    public function getCategoriesCsv()
    {
        $collection = $this->getCategories();
        $catIds = $collection->getAllIds();
        $separator = "|";
        $cat = $this->_categoryFactory->create();
        $csv = "";

        foreach ($catIds as $catId) {
            $category = $cat->load($catId);
            if($category->getLevel() != 0) {
                $catPath = explode('/',$category->getPath());
                $catParent = $catPath[sizeof($catPath)-2];
                $csv .= $category->getId().$separator;
                if($category->getLevel() == 1) {
                    $csv .= "".$separator;
                }
                else {
                    $csv .= $catParent.$separator;
                }
                $csv .= trim($category->getName())."\n";
            }
        }
        return $csv;
    }

    /**
     * @return \Magento\Store\Api\Data\StoreInterface[]
     */
    public function getStores() {
        return $this->_storeManager->getStores();
    }

    /**
     * @return string
     */
    public function getStoreList()
    {
        $separator = "|";
        $csv = "";
        $allStores = $this->getStores();
        foreach ($allStores as $eachStoreId => $val)
        {
            $storeCode = $this->_storeManager->getStore($eachStoreId)->getCode();
            $storeName = $this->_storeManager->getStore($eachStoreId)->getName();
            $storeId = $this->_storeManager->getStore($eachStoreId)->getId();
            $storeActive = $this->_storeManager->getStore($eachStoreId)->getIsActive();
            $storeUrl = $this->_storeManager->getStore($eachStoreId)->getHomeUrl();
            $csv .= $storeId.$separator;
            $csv .= $storeName.$separator;
            $csv .= $storeCode.$separator;
            $csv .= $storeActive.$separator;
            $csv .= $storeUrl.$separator;
            $csv .= "\n";
        }
        return $csv;
    }

    /**
     * @param Product $product
     * @return string
     */
    protected function getProductCategoriesCsv($product)
    {
        $separator = "^^";
        $catIds = $product->getCategoryIds();
        $csv = "";
        foreach ($catIds as $catId) {
            $csv .= $separator.$catId;
        }
        return substr($csv, 2);
    }

    /**
     * @param Product $product
     * @return string
     */
    protected function getProductName($product)
    {
        $productName = $product->getName();
        $productName = str_replace("\n", "", strip_tags($productName));
        $productName = str_replace("\r", "", strip_tags($productName));
        $productName = str_replace("\t", " ", strip_tags($productName));
        return $productName;
    }

    /**
     * @param Product $product
     * @param string $storeId
     * @return int|string
     */
    protected function getProductId($product, $storeId)
    {
        $idType = $this->_scopeConfig->getValue('recommendationexp/settings/productid', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
        if($idType == '1') {
            $productId = $product->getSku();
        } else {
            $productId = $product->getId();
        }
        return $productId;
    }

    protected function getProductImage($product)
    {
        return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . "catalog/product" . $product->getImage();
    }

    /**
     * @param Product $product
     * @return float
     */
    protected function getProductPrice($product)
    {
        if ($product->getSpecialPrice()
            && (date("Y-m-d G:i:s") > $product->getSpecialFromDate() || !$product->getSpecialFromDate())
            && (date("Y-m-d G:i:s") < $product->getSpecialToDate() || !$product->getSpecialToDate())) {
            $price = $product->getPriceInfo()->getPrice('special_price')->getAmount()->getValue();
        } else {
            $price = $product->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue();
        }
        return (float)$price;
    }

    /**
     * @param Product $product
     * @return float
     */
    protected function getProductPriceOld($product)
    {
        return $product->getPrice();
    }

    /**
     * @param Product $product
     * @return string
     */
    protected function getProductNew($product)
    {
        $now = date("Y-m-d");
        $newsFrom = substr($product->getData('news_from_date'),0,10);
        $newsTo = substr($product->getData('news_to_date'),0,10);
        if($now >= $newsFrom && $now <= $newsTo) {
            return '1';
        }
        return '0';
    }

    /**
     * @param Product $product
     * @return string
     */
    protected function getProductBrand($product)
    {
        $manufacturer = "";
        if ($product->getResource()->getAttribute('manufacturer')) {
            $manufacturer = $product->getResource()->getAttribute('manufacturer')->getFrontend()->getValue($product);
            if (strtolower($manufacturer) == "no") {
                $manufacturer = "";
            }
        }
        return $manufacturer;
    }

    /**
     * @param Product $product
     * @return string
     */
    protected function getProductDescription($product)
    {
        $description = strip_tags($product->getData('short_description'));
        $description = preg_replace("/\r|\n/s", "", $description);
        return substr($description, 0, 100);
    }

    /**
     * @param Product $product
     * @return int
     */
    protected function getProductQty($product)
    {
        return (int)$this->_stockStateInterface->getStockQty($product->getId());
    }
}