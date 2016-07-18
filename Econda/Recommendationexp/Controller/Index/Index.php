<?php

namespace Econda\Recommendationexp\Controller\Index;

use Magento\Framework\App\Action\Context;

class Index extends \Magento\Framework\App\Action\Action
{
    /** @var $_helper \Econda\Recommendationexp\Helper\Recommendation */
    protected $_helper;

    function __construct(Context $context, \Econda\Recommendationexp\Helper\Recommendation $helper)
    {
        $this->_helper = $helper;
        parent::__construct($context);
    }

    public function execute()
    {
        if (!isset($_GET['store']) && !isset($_GET['type'])) {
            die;
        }

        $storeId = $_GET['store'];
        $eEnabled = $this->_helper->isEnabled($storeId);
        $remote = $this->_helper->getRemote($storeId);

        if (trim($remote) != "") {
            $remoteAddress = $_SERVER['REMOTE_ADDR'] == $remote ? true : false;
        } else {
            $remoteAddress = true;
        }
        if ($eEnabled != '1' || !$remoteAddress) {
            die;
        }
        if ($_GET['type'] != '1' && $_GET['type'] != '2' && $_GET['type'] != '0') {
            die;
        }

        $actStore = null;
        $stores = $this->_helper->getStores();
        foreach ($stores as $store => $val) {
            if ($store == $storeId) {
                $actStore = $storeId;
            }
        }
        if ($actStore == null) {
            die;
        }
        
        $csv = "";

        // Products
        if ($_GET['type'] == '1') {
            $filename = "products.csv";
            $csv .= "ID|Name|Description|ProductURL|ImageURL|Price|OldPrice|New|Stock|SKU|Brand|ProductCategory\n";
            $products = $this->_helper->getProducts($actStore);
            foreach ($products as $product) {
                $csv .= $this->_helper->getProductCsv($product, $actStore) . "\n";
            }
        } // Categories
        else if ($_GET['type'] == '2') {
            $filename = "categories.csv";
            $csv .= "ID|ParentID|Name\n";
            $csv .= $this->_helper->getCategoriesCsv();
        } // Get Store list
        else if ($_GET['type'] == '0') {
            $filename = "stores.csv";
            $csv .= "ID|Name|Code|isActive|homeUrl\n";
            $csv .= $this->_helper->getStoreList();
        } else {
            die;
        }

        $csv = trim($csv);

        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=$filename");
        header("Content-Description: csv File");
        header("Content-length: " . strlen($csv));
        header("Pragma: no-cache");
        header("Expires: 0");
        echo $csv;
    }
}