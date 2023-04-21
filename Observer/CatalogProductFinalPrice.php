<?php
namespace Lordhair\Customizations\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\SerializerInterface;

class CatalogProductFinalPrice implements ObserverInterface
{
    /**
     * @var Magento\Framework\Serialize\SerializerInterface $serializer
     */
    private $serializer;

    public function __construct(
        SerializerInterface $serializer
    ) {
        $this->serializer = $serializer;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        //Get Product From Event
        $product = $observer->getEvent()->getProduct();//302471
        $specialPrice = $product->getSpecialPrice();

        if(isset($specialPrice)){
            $productPriceUsd = $specialPrice;
        } else {
            $productPriceUsd = $product->getPrice();
        }

        $quoteItem = $product->getQuoteItem();

        if(!$quoteItem){
            return;
        }
        
        $itemId = $quoteItem->getId();
        if(!$itemId){
            return;
        }

    }

    public function setProductPrice($item)
    {
        $this->setCustomOptionPrice($item);
        $item->getProduct()->setIsSuperMode(true);
    }

    public function setCustomOptionPrice($item)
    {
        $getOrderOptions = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());
        $customOptions = $getOrderOptions['info_buyRequest'];
        $product = $item->getProduct();
        $itemId = $item->getId();
        $options = $product->getOptions();
        $selectedValues = array();
        $optionId = null;
        $option = null;
        $selectedOption = null;
        $specialPrice = $product->getSpecialPrice();
        $isStockProd = 1;
        if(isset($specialPrice)){
            $productPriceUsd = $specialPrice;
        } else {
            $productPriceUsd = $product->getPrice();
        }

        if ($options && is_array($options)) {
            foreach($options as $option) {
                if ($option && $option->getTitle() == 'Upgrade price rule') {
                    $optionId = $option->getId();
                    $selectedOption = $item->getOptionByCode('option_' . $optionId);
                    if(!$selectedOption){
                        return;
                    }
                    $selectedValue = $selectedOption->getValue();
                    $selectedValues = explode(',', $selectedValue);
                    break;
                }
            }

            $hairTypeRemiValueId = null;
            $hairTypeEuropeValueId = null;
            $hairTypeDynmicPrice = 0;
            $hDMediumValueId = null;
            $hDMediumHeavyValueId = null;
            foreach($selectedValues as $valueId){
                $value = $option->getValueById($valueId);
                if($value){
                    if(trim($value->getTitle()) == 'Remy hair (best)'){
                        $hairTypeRemiValueId = $valueId;
                    } elseif(trim($value->getTitle()) == 'European hair (fine, thin & soft, 7" and up is not available)'){
                        $hairTypeEuropeValueId = $valueId;
                    }elseif(strpos(trim($value->getTitle()), 'Medium 120') !== false){
                        $hDMediumValueId = $valueId;
                    }elseif(strpos(trim($value->getTitle()), 'Medium heavy') !== false){
                        $hDMediumHeavyValueId = $valueId;
                    }elseif(trim($value->getTitle()) != 'Rush service' && !preg_match('/have hair cut/i', $value->getTitle()) && !preg_match('/Rush ship back/i', $value->getTitle()) && !preg_match('/hair cut-in/i', $value->getTitle()) ) {
                        $productPriceUsd += $value->getPrice();
                    }
                }
            }

            if($option){

                if (isset($customOptions['optionJsonValue'])) {
                    $isStockProd = 0;
                    $getJsonString = $customOptions['optionJsonValue']['priceUpgrade'];
                    $_convertToArray = $this->serializer->unserialize($getJsonString);
                    foreach($_convertToArray as $key=>$value){
                        if ($key == 'm_hair_type') {
                            if($hairTypeRemiValueId) {
                                $optionValue = $option->getValueById($hairTypeRemiValueId);
                                $optionValue->setPrice($value);
                                $optionValue->setDefaultPrice($value);
                            }
                            if($hairTypeEuropeValueId) {
                                $optionValue = $option->getValueById($hairTypeEuropeValueId);
                                $optionValue->setPrice($value);
                                $optionValue->setDefaultPrice($value);
                            }
                        }
                        if ($key == 'm_hair_density') {
                            if($hDMediumValueId) {
                                $optionValue = $option->getValueById($hDMediumValueId);
                                $optionValue->setPrice($value);
                                $optionValue->setDefaultPrice($value);
                            }
                            if($hDMediumHeavyValueId) {
                                $optionValue = $option->getValueById($hDMediumHeavyValueId);
                                $optionValue->setPrice($value);
                                $optionValue->setDefaultPrice($value);
                            }
                        }
                    }
                }

                if ($isStockProd) {
                    if($hairTypeRemiValueId) {
                        $hairTypeDynmicPrice = $productPriceUsd * 0.4;
                        $value = $option->getValueById($hairTypeRemiValueId);
                        $value->setPrice($hairTypeDynmicPrice);
                        $value->setDefaultPrice($hairTypeDynmicPrice);
                    }
                    if($hairTypeEuropeValueId) {
                        $hairTypeDynmicPrice = $productPriceUsd * 0.3;
                        $value = $option->getValueById($hairTypeEuropeValueId);
                        $value->setPrice($hairTypeDynmicPrice);
                        $value->setDefaultPrice($hairTypeDynmicPrice);
                    }
                    if($hDMediumValueId) {
                        $productPriceUsd = $productPriceUsd + $hairTypeDynmicPrice;
                        $productPriceUsd = $productPriceUsd * 0.15;
                        $value = $option->getValueById($hDMediumValueId);
                        $value->setPrice($productPriceUsd);
                        $value->setDefaultPrice($productPriceUsd);
                    }
                    if($hDMediumHeavyValueId) {
                        $productPriceUsd = $productPriceUsd + $hairTypeDynmicPrice;
                        $productPriceUsd = $productPriceUsd * 0.25;
                        $value = $option->getValueById($hDMediumHeavyValueId);
                        $value->setPrice($productPriceUsd);
                        $value->setDefaultPrice($productPriceUsd);
                    }
                }
            }
        }
    }
}