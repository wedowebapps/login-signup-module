<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Lordhair\Customizations\Model\Quote\Total;

use Magento\Store\Model\ScopeInterface;
use Magento\Quote\Model\QuoteValidator;
use Magento\Checkout\Model\Cart;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Lordhair\CartEdit\Helper\Data as CustomizationHelper;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Pricing\PriceCurrencyInterface;

class Subtotal extends \Magento\Quote\Model\Quote\Address\Total\AbstractTotal
{
    protected $helperData;
    protected $quoteValidator = null;
    protected $_cart;
    protected $json;
    protected $priceCurrency;
    protected $_customizationHelper;

    public function __construct(
        QuoteValidator $quoteValidator,
        Cart $cart,
        SerializerInterface $serializer,
        Json $json,
        PriceCurrencyInterface $priceCurrency,
        CustomizationHelper $customizationHelper
    )
    {
        $this->quoteValidator = $quoteValidator;
        $this->_cart = $cart;
        $this->serializer = $serializer;
        $this->json = $json;
        $this->priceCurrency = $priceCurrency;
        $this->_customizationHelper = $customizationHelper;
    }

    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    )
    {
        parent::collect($quote, $shippingAssignment, $total);
        if (!count($shippingAssignment->getItems())) {
            return $this;
        }
        $address = $shippingAssignment->getShipping()->getAddress();
        $items = $shippingAssignment->getItems();
        foreach ($items as $item) {
            $this->_initItem($address, $item);
        }

        return $this;
    }

    protected function _initItem($address, $item)
    {
        $quoteItem = $item;
        $product = $quoteItem->getProduct();
        $product->setCustomerGroupId($quoteItem->getQuote()->getCustomerGroupId());
        if ($item->getQuote()->getIsSuperMode()) {
            if (!$product) {
                return false;
            }
        }
        else {
            if (!$product || !$product->isVisibleInCatalog()) {
                return false;
            }
        }
        if ($quoteItem->getParentItem() && $quoteItem->isChildrenCalculated()) {
            $this->setProductPrice($item);
            $finalPrice = $quoteItem->getParentItem()->getProduct()->getPriceModel()->getChildFinalPrice(
                $quoteItem->getParentItem()->getProduct(),
                $quoteItem->getParentItem()->getQty(),
                $quoteItem->getProduct(),
                $quoteItem->getQty()
            );
            $finalPrice = $this->priceCurrency->convertAndRound($finalPrice);
            $item->setCustomPrice($finalPrice)
            ->setOriginalCustomPrice($finalPrice)
            ->getProduct()->setIsSuperMode(true);
            $item->calcRowTotal();
        } else if (!$quoteItem->getParentItem()) {
            $this->setCustomOptionPrice($item);
            $finalPrice = $product->getFinalPrice($quoteItem->getQty(), $item); //Modified By Vitaliy
            $finalPrice = $this->priceCurrency->convertAndRound($finalPrice);
            $item->setCustomPrice($finalPrice)
                ->setOriginalCustomPrice($finalPrice)
                ->getProduct()->setIsSuperMode(true);
            $item->calcRowTotal();
        }
        return true;
    }

    protected function priceForHairType(){
        return [
            'm_base_size_section',
            'm_base_size_frontal',
            'm_base_size_topper',
            'm_base_size',
            'm_hair_length'
        ];
    }

    protected function recalculate($newPrice,$optionTitle){
        $priceRule = 0;
        switch (trim($optionTitle)) {
            case 'Remy hair (best)':
                $priceRule = array_sum($newPrice) * 0.4;
                break;
            case 'European hair (fine&comma; thin & soft&comma; 7" and up is not available)':
                $priceRule = array_sum($newPrice) * 0.3;
                break;
            case 'Medium 120%':
                $priceRule = array_sum($newPrice) * 0.05;
                break;
            case 'Medium heavy 140%':
                $priceRule = array_sum($newPrice) * 0.15;
                break;
            case 'Heavy':
                $priceRule = array_sum($newPrice) * 0.25;
                break;
        }
        return $priceRule;
    }

    protected function getOptionPriceArray($product) {
        $prices = array();
        $options = (array)$product->getOptions();
        foreach ($options as $option) {
            if ($option->getTitle() == 'Upgrade price rule') {
                $opVals = $option->getValues() ? $option->getValues() : [];
                foreach ($opVals as $op) {
                    $prices[$op->getOptionTypeId()] = $op->getPrice();
                }
                break;
            }
        }
        return $prices;
    }

    protected function isJSON($string){
        return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }

    public function setCustomOptionPrice($item)
    {
        $product = $item->getProduct();
        $itemId = $item->getId();
        $options = $product->getOptions();

        $orderDetails = array();
        $optionId = null;
        $option = null;

        if ($options && is_array($options)) {
            $checkOldCart = false;
            foreach($options as $option) {
                if ($option && $option->getTitle() == 'Order details') {
                    $orderDetails = $item->getOptionByCode('option_' . $option->getId());
                    if(!$orderDetails){
                        return;
                    }
                    $orderDetails = $orderDetails->getValue();
                    $checkNewStyel = $this->isJSON($orderDetails);
                    if($checkNewStyel){
                        $orderDetails = $this->json->unserialize($orderDetails);
                    }else{
                        $checkOldCart = true;
                        $orderDetails = explode(',', $orderDetails);
                    }
                    break;
                }
            }

            foreach($options as $option) {
                if ($option && $option->getTitle() == 'Upgrade price rule') {
                    $upgradePrices = $item->getOptionByCode('option_' . $option->getId());
                    if(!$upgradePrices){
                        return;
                    }
                    $selectedValue = $upgradePrices->getValue();
                    $selectedValues = explode(',', $selectedValue);
                    break;
                }
            }

            if($option){
                $customOptions = $product->getCustomOptions();
                $product->setCustomOptions([]);
                $specialPrice = $product->getFinalPrice();
                $product->setCustomOptions($customOptions);
                $hairTypePriceArr = array(
                    'specialPrice' => $specialPrice
                );
                if(!isset($specialPrice)){
                    $hairTypePriceArr = array(
                        'specialPrice' => $product->getPrice()
                    );
                }

                $hairTypePrice = NULL;
                $hairDensityPrice = NULL;

                $hairTypeTitle = NULL;
                $hairTypePriceId = NULL;
                $hairDensityTitle = NULL;
                $hairDensityPriceId = NULL;

                $prices = $this->getOptionPriceArray($product);

                if ($checkOldCart){
                    $isCalculate = true;
                    foreach($selectedValues as $valueId){
                        $value = $option->getValueById($valueId);
                        if (!$value){
                            continue;
                        }
                        if(trim($value->getTitle()) == 'Remy hair (best)'){
                            $hairTypeTitle = 'Remy hair (best)';
                            $hairTypePriceId = $valueId;
                        } elseif(trim($value->getTitle()) == 'European hair (fine&comma; thin & soft&comma; 7" and up is not available)'){
                            $hairTypeTitle = 'European hair (fine&comma; thin & soft&comma; 7" and up is not available)';
                            $hairTypePriceId = $valueId;

                        }elseif(strpos(trim($value->getTitle()), 'Medium 120%') !== false){
                            $hairDensityTitle = 'Medium 120%';
                            $hairDensityPriceId = $valueId;

                        }elseif(strpos(trim($value->getTitle()), 'Medium heavy 140%') !== false){
                            $hairDensityTitle = 'Medium heavy 140%';
                            $hairDensityPriceId = $valueId;

                        }elseif(strpos(trim($value->getTitle()), 'Heavy') !== false){
                            $hairDensityTitle = 'Heavy';
                            $hairDensityPriceId = $valueId;

                        }elseif(
                            !preg_match('/mm/i', trim($value->getTitle())) && !preg_match('/Spot\/Dot/i', trim($value->getTitle()))
                            && !preg_match('/Human grey hair/i', trim($value->getTitle())) && !preg_match('/Root color/i', trim($value->getTitle()))
                            && !preg_match('/Bleach knots/i', trim($value->getTitle())) && !preg_match('/Rush service/i', trim($value->getTitle())) && !preg_match('/Pick-up/i', trim($value->getTitle()))
                            && !preg_match('/have hair cut/i', $value->getTitle()) && !preg_match('/Rush ship back/i', $value->getTitle())
                            && !preg_match('/hair cut-in/i', trim($value->getTitle()))  && !preg_match('/your hairstyles/i', trim($value->getTitle()))
                            && !preg_match('/order my length/i', trim($value->getTitle())) && !preg_match('/send email to Lordhair/i', trim($value->getTitle()))
                            && !preg_match('/Upload hairstyle images/i', trim($value->getTitle())) ) {
                                $hairTypePriceArr[] = $value->getPrice();
                        }
                    }
                } else{
                    foreach($orderDetails as $item) {

                        if ($item['attribute_value'] == 'm_hair_type') {
                            $hairTypeTitle = $item['optionTitle'];
                            if (isset($item['priceRuleId'])) {
                                $hairTypePriceId = $item['priceRuleId'];
                            }
                            continue;
                        }

                        if ($item['attribute_value'] == 'm_hair_density') {
                            $hairDensityTitle = $item['optionTitle'];
                            if (isset($item['priceRuleId'])) {
                                $hairDensityPriceId = $item['priceRuleId'];
                            }
                            continue;
                        }

                        //checking item options exist of not
                        if (isset($item['childOptions'])) {
                            foreach($item['childOptions'] as $child){
                                if(in_array($child['attribute_value'], $this->priceForHairType()) && $child['priceRuleId']){
                                    $hairTypePriceArr[$child['attribute_value']] = $prices[$child['priceRuleId']];
                                }
                            }
                        }

                        //Adding prices for hairTypePrice and hairDensityPrice step
                        if(in_array($item['attribute_value'], $this->priceForHairType()) && $item['priceRuleId']){
                            $hairTypePriceArr[$item['attribute_value']] = $prices[$item['priceRuleId']];
                        }
                    }
                }

                if ($hairTypePriceId) {
                    $hairTypePrice = $this->recalculate($hairTypePriceArr,$hairTypeTitle);
                }

                if ($hairDensityPriceId) {
                    $hairTypePriceArr[] = $hairTypePrice;
                    $hairDensityPrice = $this->recalculate($hairTypePriceArr,$hairDensityTitle);
                }

                if($hairTypePriceId) {
                    $value = $option->getValueById($hairTypePriceId);
                    $value->setPrice($hairTypePrice);
                    $value->setDefaultPrice($hairTypePrice);
                }

                if($hairDensityPriceId) {
                    $value = $option->getValueById($hairDensityPriceId);
                    $value->setPrice($hairDensityPrice);
                    $value->setDefaultPrice($hairDensityPrice);
                }
            }
        }
    }
}
