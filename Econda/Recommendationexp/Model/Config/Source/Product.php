<?php

namespace Econda\Recommendationexp\Model\Config\Source;

use Magento\Framework\App\ObjectManager;

class Product implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '0', 'label' => __('Magento Product ID')],
            ['value' => '1', 'label' => __('SKU')]
        ];
    }
}
