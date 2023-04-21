<?php
/**
 * Copyright © Lordhair, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Lordhair\Customizations\Helper;

use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\ScopeInterface;

/**
 * Catalog data helper
 *
 * @api
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class ValidateCartItems extends \Magento\Framework\App\Helper\AbstractHelper {
    
    const XML_PATH_CLOSE_TIER_PRICE_SCOPE = 'catalog/price/close_tier_price';

    protected $checkoutSession;
    protected $productConfig;
    protected $productFactory;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Helper\Product\Configuration $productConfig,
        \Magento\Catalog\Model\ProductFactory $productFactory
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->productConfig = $productConfig;
        $this->productFactory = $productFactory;
        parent::__construct($context);
    }

    /**
     * Check if the store is configured to use static URLs for media
     *
     * @return bool
     */
    public function isCloseTierPrice() {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CLOSE_TIER_PRICE_SCOPE,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function veryCartItemsIsRequired() {

        $this->checkoutSession->getQuote();

        if (!$this->checkoutSession->hasQuote() || !$this->checkoutSession->getQuoteId()) {
            return true;
        }

        $items = $this->checkoutSession->getQuote()->getAllVisibleItems();
        
        if (count($items) <= 0) {
            return true;
        }

        $rendererBlock = ObjectManager::getInstance()->create(\Lordhair\CartEdit\Block\Cart\Item\Renderer::class, ['data' => []]);

        foreach ($items as $item) {
            $rendererBlock->setItem($item);
            $product = $item->getProduct();
            $sortAttrRull = $rendererBlock->getStepsSortedArr($product);
            $_options = $rendererBlock->getOptionList();
            if (!$_options) {
                $_options = $rendererBlock->getSelectedOptions($product->getId());
            }
            $requiredOptions = $rendererBlock->getRequiredOptions($product);
            if ($_options) {
                foreach ($_options as $_option) {
                    $_formatedOptionValue = $rendererBlock->getFormatedOptionValue($_option);
                    if(stristr($rendererBlock->escapeHtml($_option['label']), 'Order details')) {
                        $_fullViewOption =  isset($_formatedOptionValue['full_view']) ? $_formatedOptionValue['full_view'] : $_formatedOptionValue['value'];
                        $string  = str_replace('&quot;', '"', $_fullViewOption);
                        $string  = str_replace('&#39;', "'", $string);
                        $string  = str_replace('&amp;', '&', $string);
                        $string  = str_replace('&lt;', '<', $string);
                        $string  = str_replace('&gt;', '>', $string);
                        $string  = str_replace(';;', ';', $string);
                        $string  = str_replace("'", "\'", $string);
                        $string = preg_replace('/(\'|&#0*39;)/', 'spchar39', $string);
                        $string = str_replace('&comma;', ',', $string);
                        $attrArray = explode(";", $string);
                        $attrArray = $rendererBlock->getRemainingAttr($attrArray,$item);
                        $_optionsArray = array();
                        foreach (array_unique($attrArray) as $sort) {
                            $sort = str_replace('&amp;', '&', $sort);
                            $arr  = explode(':', $sort);
                            switch ($arr[0]) {
                                case 'Base Size':
                                    $arr[0] = 'Hair length';
                                    break;
                                case 'Hair Length':
                                    $arr[0] = 'Hair size';
                                    break;
                            }
                            if(array_key_exists(trim($arr[0]), $sortAttrRull)) {
                                $_optionsArray[trim($arr[0])]  = $arr[1];
                            }
                        }

                        $ordered = array();
                        //array sorting by defined array
                        foreach ($sortAttrRull as $key=>$value) {
                            if (array_key_exists($key, $_optionsArray)) {
                                $ordered[$key] = $_optionsArray[$key];
                                unset($_optionsArray[$key]);
                            }
                        }

                        $_optionsArray = array_merge($ordered,$_optionsArray);

                        foreach ($_optionsArray as $key=>$value) {
                            if ($value == 'Inserisci le tue istruzioni aggiuntive.' || $value == 'Please type in your additional instruction.') {
                                $value = '';
                            }
                            if($key == 'Hair color option'){
                                $hair_color_option_out_of_stock = $product->getResource()->getAttribute('m_hair_color_for_out_stock')->getFrontend()->getValue($product);
                                if(!is_array($hair_color_option_out_of_stock)){
                                    $hair_color_option_out_of_stock = explode(',', $hair_color_option_out_of_stock);
                                }
                                $hair_color_option_out_of_stock = array_map('trim',$hair_color_option_out_of_stock);

                                if(!is_array($value)){
                                    $valuee = explode(',', $value);
                                }
                                $valuee = array_map('trim', $valuee);

                                $result = array_intersect($valuee,$hair_color_option_out_of_stock);
                                if (sizeof($result) > 0 || $value == '' && in_array($key, $requiredOptions)) {
                                    return false;
                                }
                            }else {
                                if ($value == '' && in_array($key, $requiredOptions)) {
                                    return false;
                                }
                            }
                        }
                    }
                }
            }
        }

        return true;
    }
}