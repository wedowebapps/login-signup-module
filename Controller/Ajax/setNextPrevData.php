<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Lordhair\Customizations\Controller\Ajax;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Session as CatalogSession;
use Magento\Customer\Model\SessionFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\View\Result\PageFactory;
use Lordhair\Customizations\Helper\Data as CustomizationHelper;
use Magento\Checkout\Model\Cart;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\ArrayUtils;

/**
 * SetNextPrevData controller
 */
class SetNextPrevData extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @var Magento\Catalog\Model\ProductFactory $productloader
     */
    protected $_productloader;

    /**
     * @var Magento\Catalog\Model\Session $catalogSession
     */
    protected $_catalogSession;

    /**
     * @var Lordhair\Customizations\Helper\Data $CustomizationHelper
     */
    protected $_customizationHelper;

    /**
     * @var Magento\Checkout\Model\Cart $cart
     */
    private $cart;

    /**
     * @var Magento\Framework\Serialize\SerializerInterface $serializer
     */
    private $serializer;

    /**
     * Initialize GetAllSteps controller
     */
    public function __construct(
        Context $context,
        SessionFactory $SessionFactory,
        JsonFactory $resultJsonFactory,
        RawFactory $resultRawFactory,
        PageFactory $resultPageFactory,
        ProductFactory $productloader,
        CatalogSession $catalogSession,
        CustomizationHelper $customizationHelper,
        Cart $cart,
        ArrayUtils $arrayUtils,
        SerializerInterface $serializer
    ) {

        $this->customerSession = $SessionFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->_resultPageFactory = $resultPageFactory;
        $this->_productloader = $productloader;
        $this->_catalogSession = $catalogSession;
        $this->_customizationHelper = $customizationHelper;
        $this->cart = $cart;
        $this->arrayUtils = $arrayUtils;
        $this->serializer = $serializer;
        parent::__construct($context);
    }

    public function execute()
    {
        $httpBadRequestCode = 400;
        $resultRaw = $this->resultRawFactory->create();

        try {
            $getPostParams = [
                'pId'           => $this->getRequest()->getPost('pid'),
                'currentStep'   => $this->getRequest()->getPost('currentStep'),
                'optionData'    => $this->getRequest()->getPost('optionData'),
                'isPrevClicked'    => $this->getRequest()->getPost('isPrevClicked')
            ];
        } catch (\Exception $e) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }

        if (!$getPostParams || $this->getRequest()->getMethod() !== 'POST' || !$this->getRequest()->isXmlHttpRequest()) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }

        $response = [
            'errors' => false,
            'data' => array(),
            'message' => __('Success')
        ];

        try {
            $level = 0;
            $pId = $getPostParams['pId'];
            $product = $this->_productloader->create()->load($pId);
            $storeId = $this->_customizationHelper->getStore()->getId();
            $currentOptionHtml = '';
            $leftSideMainHtml = '';
            $redirectCart = 0;

            $this->setSessionData('doneOrNotStep','');
            if ($getPostParams['optionData']['currentAttr'] && $getPostParams['optionData']['currentAttr'] == 'm_add_to_cart'){
                // whether there are required options not to selected
                $step = $this->_customizationHelper->getFirstOptionByRequiredOptions($product, $this->_resultPageFactory->create());
                if ($step) {
                    $response['data']['backStep'] = $step['step'];
                    $resultJson = $this->resultJsonFactory->create();
                    return $resultJson->setData($response);
                }
            }
            $currentOptionHtml = $this->getCurrentOptionHtml($product, $getPostParams);

            $finalPrice = $this->getUpgradedPrice($product);



            if ($getPostParams['optionData']['currentAttr'] && $getPostParams['optionData']['currentAttr'] == 'm_add_to_cart'){
                $currentOptionHtml = '';
                $productAddToCart = $this->productAddToCart($product);
                $redirectCart = $this->getSiteMainUrl().'checkout/cart';
                $this->setSessionData('selectedOptions', array());
            }

            $currencyCode = $this->getCurrentCurrencyCode();

            $productDetails = array(
                'id' => $product->getId(),
                'title' => $product->getName(),
                'basePrice' => $product->getPriceInfo()->getPrice('regular_price')->getValue(),
                'finalPrice' => str_replace(',','',$finalPrice),
                'price' => $product->getPriceInfo()->getPrice('special_price')->getValue(),
                'currencySymbol' => $currencyCode,
                'currency' => ''
            );

            $response['data'] = array (
                'leftBgImage'       => $this->getSiteMainUrl().'pub/media/catalog/product' . $product->getImage(),
                'productDetails'    => $productDetails,
                'currentOptionHtml' => $currentOptionHtml,
                'currentOptionLevel'=> $this->getSessionData('currentOptionLevel'),
                'doneOrNotStep'     => $this->getSessionData('doneOrNotStep'),
                'redirectCart'      => $redirectCart,
                'subTitle'          => $this->getSessionData('subTitle')
            );

        } catch (LocalizedException $e) {
            $response = [
                'errors' => true,
                'errorType' => 'localized',
                'message' => $e->getMessage()
            ];
        } catch (\Exception $e) {
            $this->setSessionData('selectedOptions', array());
            $response = [
                'errors' => true,
                'errorType' => 'exception',
                'message' => __('Invalid product please try agian later or contact store admin. ').$e->getMessage()
            ];
        }
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($response);
    }

    public function setCurrentSetpSession($product,$getPostParams) {
        $_getSelectedOptions = $this->getSessionData('selectedOptions');
        if (!$_getSelectedOptions) {
            $setSelectedOptionsArr = array (
                'steps'         => $this->_customizationHelper->getTotalSteps($product),
                'base_prices'   => array(),
                'current_price' => $product->getPrice(),
                'productId'     => $product->getID()
            );
            $this->setSessionData('selectedOptions', $setSelectedOptionsArr);
            $_getSelectedOptions = $setSelectedOptionsArr;
        }
        $newSteps = array();
        $getStepFromAjax = $getPostParams['currentStep'];
        foreach ($_getSelectedOptions['steps'] as $singleStep) {
            $singleStep['current'] = '';
            if ($singleStep['step'] == $getStepFromAjax) {
                $singleStep['current'] = true;
            }
            $newSteps[] = $singleStep;
        }
        $_getSelectedOptions['steps'] = $newSteps;
        return $_getSelectedOptions;
    }

    public function checkPreveEnabledDisabled()
    {
        $_getSelectedOptions = $this->getSessionData('selectedOptions');
        $return = 0;
        foreach ($_getSelectedOptions['steps'] as $singleStep) {
            if ($singleStep['current'] && count($singleStep['options']) > 0) {
                $return = 1;
            }
        }
        return $return;
    }

    public function getCurrentOptionHtml($product, $getPostParams)
    {
        $resultPage = $this->_resultPageFactory->create();

        $_getSelectedOptions = $this->setCurrentSetpSession($product,$getPostParams);

        $this->setSessionData('selectedOptions', $_getSelectedOptions);

        $data = array(
            'product' => $product,
            'getPostParams' => $getPostParams
        );

        foreach($_getSelectedOptions['steps'] as $step){
            if ($step['current']) {
                switch ($step['attribute_value']) {

                    case 'm_base_material_color':
                        $block = $resultPage->getLayout()
                        ->createBlock('Lordhair\Customizations\Block\MaterialColor')
                        ->setTemplate('Lordhair_Customizations::Steps/MaterialColor.phtml')
                        ->setData('data',$data)
                        ->toHtml();
                        break;

                    case 'm_base_design':
                        $block = $resultPage->getLayout()
                        ->createBlock('Lordhair\Customizations\Block\BaseDesign')
                        ->setTemplate('Lordhair_Customizations::Steps/BaseDesign.phtml')
                        ->setData('data',$data)
                        ->toHtml();
                        break;

                    case 'm_front_contour':

                        $block = $resultPage->getLayout()
                        ->createBlock('Lordhair\Customizations\Block\FrontContour')
                        ->setTemplate('Lordhair_Customizations::Steps/FrontContour.phtml')
                        ->setData('data',$data)
                        ->toHtml();
                        break;

                    case 'm_hair_length':

                        $block = $resultPage->getLayout()
                        ->createBlock('Lordhair\Customizations\Block\HairLength')
                        ->setTemplate('Lordhair_Customizations::Steps/HairLength.phtml')
                        ->setData('data',$data)
                        ->toHtml();
                        break;

                    case 'm_hair_density':

                        $block = $resultPage->getLayout()
                        ->createBlock('Lordhair\Customizations\Block\HairDensity')
                        ->setTemplate('Lordhair_Customizations::Steps/HairDensity.phtml')
                        ->setData('data',$data)
                        ->toHtml();
                        break;

                    case 'm_curl_wave':

                        $block = $resultPage->getLayout()
                        ->createBlock('Lordhair\Customizations\Block\CurlWave')
                        ->setTemplate('Lordhair_Customizations::Steps/CurlWave.phtml')
                        ->setData('data',$data)
                        ->toHtml();
                        break;

                    case 'm_curl_wave_type':

                        $block = $resultPage->getLayout()
                        ->createBlock('Lordhair\Customizations\Block\CurlWaveWomen')
                        ->setTemplate('Lordhair_Customizations::Steps/CurlWaveWomen.phtml')
                        ->setData('data',$data)
                        ->toHtml();
                        break;

                    case 'm_hair_direction':

                        $block = $resultPage->getLayout()
                        ->createBlock('Lordhair\Customizations\Block\Hairdirection')
                        ->setTemplate('Lordhair_Customizations::Steps/Hairdirection.phtml')
                        ->setData('data',$data)
                        ->toHtml();
                        break;

                    case 'm_hair_color':

                        $block = $resultPage->getLayout()
                        ->createBlock('Lordhair\Customizations\Block\HairColor')
                        ->setTemplate('Lordhair_Customizations::Steps/HairColor.phtml')
                        ->setData('data',$data)
                        ->toHtml();
                        break;
                    case 'm_highlight':

                        $block = $resultPage->getLayout()
                        ->createBlock('Lordhair\Customizations\Block\Highlight')
                        ->setTemplate('Lordhair_Customizations::Steps/Highlight.phtml')
                        ->setData('data',$data)
                        ->toHtml();
                        break;
                    case 'm_grey_hair':

                        $block = $resultPage->getLayout()
                        ->createBlock('Lordhair\Customizations\Block\GreyHair')
                        ->setTemplate('Lordhair_Customizations::Steps/GreyHair.phtml')
                        ->setData('data',$data)
                        ->toHtml();
                        break;
                    case 'm_hair_type':

                        $block = $resultPage->getLayout()
                        ->createBlock('Lordhair\Customizations\Block\HairType')
                        ->setTemplate('Lordhair_Customizations::Steps/HairType.phtml')
                        ->setData('data',$data)
                        ->toHtml();
                        break;
                    case 'm_bleach_knots':

                        $block = $resultPage->getLayout()
                        ->createBlock('Lordhair\Customizations\Block\BleachKnots')
                        ->setTemplate('Lordhair_Customizations::Steps/BleachKnots.phtml')
                        ->setData('data',$data)
                        ->toHtml();
                        break;
                    case 'm_cut_in_and_style':

                        $block = $resultPage->getLayout()
                        ->createBlock('Lordhair\Customizations\Block\HairCut')
                        ->setTemplate('Lordhair_Customizations::Steps/HairCut.phtml')
                        ->setData('data',$data)
                        ->toHtml();
                        break;
                    case 'm_base_production_time':

                        $block = $resultPage->getLayout()
                        ->createBlock('Lordhair\Customizations\Block\ProductionTime')
                        ->setTemplate('Lordhair_Customizations::Steps/ProductionTime.phtml')
                        ->setData('data',$data)
                        ->toHtml();
                        break;
                    case 'm_additional_instruction':

                        $block = $resultPage->getLayout()
                        ->createBlock('Lordhair\Customizations\Block\Additional')
                        ->setTemplate('Lordhair_Customizations::Steps/Additional.phtml')
                        ->setData('data',$data)
                        ->toHtml();
                        break;
                    default:

                        $block = $resultPage->getLayout()
                        ->createBlock('Lordhair\Customizations\Block\BaseSize')
                        ->setTemplate('Lordhair_Customizations::Steps/BaseSize.phtml')
                        ->setData('data',$data)
                        ->toHtml();
                }
            }
        }
        return $block;
    }

    public function getUpgradedPrice($product)
    {
        $upgradedPrice = array();
        $hairTypePrice = array();
        $hairTypeExist = false;
        $hairDensityPrice = array();
        $hairDensityExist = false;
        $hairTypeAttrTitle = array();
        $newSessionArray = array();
        $hairTypePriceNew = 0;
        $_getSelectedOptions = $this->getSessionData('selectedOptions');
        $specialPrice = $product->getPriceInfo()->getPrice('special_price')->getValue();
        $originlSpecialPrice = $specialPrice;
        $baseCurCode = $this->_customizationHelper->getBaseCurrencyCode();
        $currentCurCode = $this->_customizationHelper->getCurCurrencyCode();
        $getPricesArr = $this->_customizationHelper->getOptionPriceArray($product);
        if ($currentCurCode != $baseCurCode) {
            $originlSpecialPrice = $product->getSpecialPrice();
        }
        $upgradedPrice[] = $specialPrice;
        $stepsForSelection = array(
            'm_base_size',
            //'m_base_design',
            'm_template_samples',
            'm_base_size_section',
            'm_base_size_section_full_cap',
            'm_base_size_selection_topper',
            'm_hair_length',
            //'m_bleach_knots'
        );
        $hairDensity = array(
            'm_base_size',
            //'m_base_design',
            'm_template_samples',
            'm_base_size_section',
            'm_base_size_section_full_cap',
            'm_base_size_selection_topper',
            'm_hair_length',
            //'m_bleach_knots'
        );
        foreach ($_getSelectedOptions['steps'] as $singleStep) {
            foreach ($singleStep['options'] as $key=>$options){
                if (isset($options['priceRule']) && $options['priceRule'] != '') {
                    if (in_array($key, $stepsForSelection)) {
                        $hairTypePrice[] = $options['priceRule'];
                    }
                    if (in_array($key, $hairDensity)) {
                        $hairDensityPrice[] = $options['priceRule'];
                    }
                }
            }
        }
        foreach ($_getSelectedOptions['steps'] as $singleStep) {
            foreach ($singleStep['options'] as $key=>$options){
                if ($key == 'm_hair_type' && isset($getPricesArr[trim($options['optionTitle'])])) {
                    $hairTypeExist = true;
                    $hairTypeAttrTitle = '';
                    $hairTypePriceNew = $options['priceRule'];
                    if (strcmp(trim($options['optionTitle']), 'Remy hair (best)') == 0) {
                        if (count($hairTypePrice) > 0) {
                            $hairTypePrice[] = $originlSpecialPrice;
                            $hairTypePriceNew = array_sum($hairTypePrice) * 0.4;
                        }
                    }
                    if (strcmp(str_replace('&comma;', ',', trim($options['optionTitle'])), 'European hair (fine, thin & soft, 7" and up is not available)') == 0) {
                        if (count($hairTypePrice) > 0) {
                            $hairTypePrice[] = $originlSpecialPrice;
                            $hairTypePriceNew = array_sum($hairTypePrice) * 0.3;
                        }
                    }
                    $singleStep['options'][$key]['priceRule'] = $hairTypePriceNew;
                    $options['priceRule'] = $hairTypePriceNew;
                    $upgradedPrice[] = $this->_customizationHelper->convertToBaseCurrency($options['priceRule']);

                } elseif($key == 'm_hair_density' && isset($getPricesArr[trim($options['optionTitle'])])){
                    $hairDensityExist = true;
                    $hairDensityPrice[] = $hairTypePriceNew;
                    $hairDensityPriceNew = $options['priceRule'];
                    if (strpos(trim($options['optionTitle']), 'Medium 120') !== false) {
                        if (count($hairDensityPrice) > 0) {
                            $hairDensityPrice[] = $originlSpecialPrice;
                            $hairDensityPriceNew = array_sum($hairDensityPrice) * 0.05;
                        }
                    }
                    if (strpos(str_replace('&comma;', ',', trim($options['optionTitle'])), 'Medium heavy') !== false) {
                        if (count($hairDensityPrice) > 0) {
                            $hairDensityPrice[] = $originlSpecialPrice;
                            $hairDensityPriceNew = array_sum($hairDensityPrice) * 0.15;
                        }
                    }
                    if (strpos(str_replace('&comma;', ',', trim($options['optionTitle'])), 'Heavy') !== false) {
                        if (count($hairDensityPrice) > 0) {
                            $hairDensityPrice[] = $originlSpecialPrice;
                            $hairDensityPriceNew = array_sum($hairDensityPrice) * 0.25;
                        }
                    }
                    $singleStep['options'][$key]['priceRule'] = $hairDensityPriceNew;
                    $options['priceRule'] = $hairDensityPriceNew;
                    $upgradedPrice[] = $this->_customizationHelper->convertToBaseCurrency($options['priceRule']);
                } else {
                    if (isset($options['priceRule']) && $options['priceRule'] != '') {
                        $upgradedPrice[] = $this->_customizationHelper->convertToBaseCurrency($options['priceRule']);
                    }
                }
            }
            $newSessionArray[] = $singleStep;
        }
        if ($hairTypeExist || $hairDensityExist) {
            $_getSelectedOptions['steps'] = $newSessionArray;
        }
        $_getSelectedOptions['current_price'] = array_sum($upgradedPrice);
        $this->setSessionData('selectedOptions', $_getSelectedOptions);
        return array_sum($upgradedPrice);
    }

    public function setSessionData($key, $value)
    {
        return $this->_catalogSession->setData($key, $value);
    }

    public function getSessionData($key, $remove = false)
    {
        return $this->_catalogSession->getData($key, $remove);
    }

    public function getGroupAttributes($pro,$isCustom = false,$groupId = 36){

        $data = [];
        $ignoreAttr = array(
            'Hair color option out stock',
            'Base Production Time',
            'Color code',
            'Color code for Women',
            'Grey hair type',
            'I want highlights',
            'I want highlights to my hair',
            'Highlight color',
            'Highlight distribution',
            'Yes, have hair cut-in and styled',
            'I′ll fill the dimension in below Base Size section',
            'I want grey hair',
            'How much grey hair do you need?',
            'Cut Style',
            'Cut-in & Style',
            'Scallop front',
            'Production Time',
            'Old Unit'
        );

        $productAttributes = $pro->getAttributes();
        foreach ($productAttributes as $attribute){
            if ($attribute->isInGroup($pro->getAttributeSetId(), $groupId)){
                $_code = $attribute->getAttributeCode();
                $getSelectedValues = $pro->getResource()->getAttribute($_code)->getFrontend()->getValue($pro);
                if ($getSelectedValues && $getSelectedValues != '') {
                    $getSelectedValues = explode(",",$getSelectedValues);
                    if ($getSelectedValues && is_array($getSelectedValues) && count($getSelectedValues) > 0 && !in_array($attribute->getStoreLabel(), $ignoreAttr)) {
                        $data[] = $attribute->getStoreLabel();
                    }
                }
            }
        }
        $data[] = 'Additional Instruction';
        return $data;
    }

    public function checkExistAttr($attr,$attrArray)
    {
        $baseSize = 'Ispchar39ll fill the dimension in below Base Size section';
        $return = false;
        foreach ($attrArray as $item) {
            if (stripos($item, $attr) !== false) {
                $return = true;
            }
            if(stripos($item, $baseSize)!== false && stripos($attr, 'Base Size')!== false){
                $return = true;
            }
        }
        return $return;
    }

    public function productAddToCart($product)
    {
        $getSelectedSessionOptions = $this->getSessionData('selectedOptions');
        $cart = $this->cart;
        $params = array();
        $options = array();
        $optionValue = array();
        $priceUpgrade = array();
        $priceUpgradeIds = array();
        $hairCutImages = array();
        foreach ($getSelectedSessionOptions['steps'] as $step) {
            foreach ($step['options'] as $newKey => $newValue) {
                $key = '';
                if ($newValue['parentOptionName'] == 'm_hair_color' && ($newKey == 'm_color_code' || $newKey == 'm_color_code_for_women')) {
                    $newKey = 'm_color_code_hair';
                }else if ($newValue['parentOptionName'] == 'm_curl_wave_type') {
                    $newKey = 'm_curl_wave';
                }
                switch ($newKey) {
                    case 'm_template_samples':
                        $key = 'Template & samples';
                    break;
                    case 'm_base_size':
                        $key = 'Base size';
                    break;
                    case 'm_base_size_section':
                        $key = 'Base size selection';
                    break;
                    case 'm_base_design':
                        $key = 'Base design';
                    break;
                    case 'm_base_material_color':
                        $key = 'Base material color';
                    break;
                    case 'm_front_contour':
                        $key = 'Front contour';
                    break;
                    case 'm_hair_density':
                        $key = 'Hair density';
                    break;
                    case 'm_curl_wave':
                        $key = 'Curl & wave';
                    break;
                    case 'm_hair_direction':
                        $key = 'Hair direction';
                    break;
                    case 'm_hair_color':
                        $key = 'Hair color';
                    break;
                    case 'm_color_code_hair':
                        $key = 'Hair color code';
                    break;
                    case 'm_hair_color_special':
                        $key = 'Hair color Instructions';
                    break;
                    case 'm_highlight':
                        $key = 'Highlight';
                    break;
                    case 'm_highlights_type_new':
                        $key = 'Highlight Selection';
                    break;
                    case 'm_hair_length_root':
                        $key = 'Highlight Hair Length';
                    break;
                    case 'm_color_code':
                        $key = 'Highlight Color';
                    break;
                    case 'm_highlights_type_new_special':
                        $key = 'Highlight Instructions';
                    break;
                    case 'm_grey_hair':
                        $key = 'Grey hair';
                    break;
                    case 'm_want_grey_hair':
                        $key = 'Grey hair inner';
                    break;
                    case 'm_how_much_grey_hair':
                        $key = 'Grey Hair Percentage';
                    break;
                    case 'm_grey_hair_type':
                        $key = 'Grey Hair Type';
                    break;
                    case 'm_hair_type':
                        $key = 'Hair type';
                    break;
                    case 'm_bleach_knots':
                        $key = 'Bleach knots';
                    break;
                    case 'm_cut_in_and_style':
                        $key = 'Hair Cut';
                    break;
                    case 'm_base_production_time':
                        $key = 'Rush service';
                    break;
                    case 'm_hair_cut_styled_have':
                        $key = 'Cut Style My Length';
                    break;
                    case 'm_cut_style':
                        $key = 'Cut style';
                    break;
                    default:
                        $key = $step['title'];
                }
                if ($newValue['type'] == 'range') {
                    foreach($newValue['childOption'] as $childValue) {
                        $arrayOption = array(
                            'sliderInnerType' => $childValue['sliderInnerType'],
                            'attrName' => $newKey,
                            'attrValue' => htmlspecialchars($childValue['sliderValue'])
                        );
                        if ($childValue['sliderUnit'] == 'modulo') {
                            $childValue['sliderUnit'] = '%';
                        }
                        if($newKey == 'm_hair_cut_styled_have_length') {
                            $optionValue[] = $childValue['sliderType'].' '.$childValue['sliderInnerType'].':'.$childValue['sliderValue'].$childValue['sliderUnit'];
                        }elseif($newKey == 'm_hair_length_root') {
                            $optionValue[] = $key.':'.$childValue['sliderValue'].$childValue['sliderUnit'];
                        } else {
                            $optionValue[] = $childValue['sliderInnerType'].':'.$childValue['sliderValue'].$childValue['sliderUnit'];
                        }

                    }
                    if (isset($newValue['priceRule']) && $newValue['priceRule'] != '') {
                        $priceUpgrade[$newKey] = $newValue['priceRule'];
                        $priceUpgradeIds[] = $this->_customizationHelper->getOptionIdArray($product,$newValue['optionTitle']);
                    }
                } if ($newValue['type'] == 'multiple') {
                    $optionValue[] = 'Hair Cut Additional Instruction:'.str_replace("'", "spchar39", $newValue['optionTitle']);
                    $optionValue[] = $newValue['childOption']['inputType'].':'.json_encode($newValue['childOption']['inputValue']);
                    if (isset($newValue['priceRule']) && $newValue['priceRule'] != '') {
                        $priceUpgrade[$newKey] = $newValue['priceRule'];
                        $priceUpgradeIds[] = $this->_customizationHelper->getOptionIdArray($product,$newValue['optionTitle']);
                    }
                    if (isset($newValue['childOption']['inputValue'])) {
                        $hairCutImages = $newValue['childOption']['inputValue'];
                    }
                } else {
                    $arrayOption = array(
                        'key' => $key,
                        'attrName' => $newKey,
                        'attrValue' => htmlspecialchars($newValue['optionTitle'])
                    );
                    $ignoreAttr = array(
                        'm_hair_length',
                        'm_highlight_percentage',
                        'm_how_much_grey_hair_percentage',
                        'm_hair_cut_styled_have_length',
                        'm_hair_length_root',
                    );
                    if (!in_array($newKey, $ignoreAttr)) {
                        $optionValue[] = $key.':'.str_replace("'", "spchar39", $newValue['optionTitle']);
                        if (isset($newValue['priceRule']) && $newValue['priceRule'] != '') {
                            $priceUpgrade[$newKey] = $newValue['priceRule'];
                            $priceUpgradeIds[] = $this->_customizationHelper->getOptionIdArray($product,$newValue['optionTitle']);
                        }
                    }
                }
            }
        }
        $sortAttrRull = $this->getGroupAttributes($product,$isCustom = false);
        $uniqueReqAttr = array();
        foreach ($sortAttrRull as $singleAttr) {
            if (!$this->checkExistAttr($singleAttr,$optionValue)) {
                $uniqueReqAttr[] = $singleAttr.':';
            }
        }
        if (count($uniqueReqAttr) > 0) {
            $optionValue = array_merge($optionValue,$uniqueReqAttr);
        }

        //set json value  in the option
        $optionJsonValue = array (
            'selectedJson' => $this->serializer->serialize($getSelectedSessionOptions),
            'priceUpgrade' => $this->serializer->serialize($priceUpgrade),
            'hairCutImages' => $this->serializer->serialize($hairCutImages)
        );
        $optionValueString = implode(";",$optionValue);

        try{
            $getCartOptionID = $this->getCartOptionID($product,'Order details');
            $getCartPriceOptionID = $this->getCartOptionID($product,'Upgrade price rule');
            $options[$getCartOptionID] = $optionValueString;
            $options[$getCartPriceOptionID] = array_unique(array_filter($priceUpgradeIds));
            $params['product'] = $product->getId();
            $params['qty'] = 1;
            $params['options'] = $options;
            $params['optionJsonValue'] = $optionJsonValue;
            $cart->addProduct($product, $params);
            $cart->save();
            return 1;
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function getCartOptionID($product, $title)
    {
        $optID = '';
        $options = $this->decorateArray($product->getOptions());
        foreach($options as $opt)
        {
            if($opt->getTitle() == $title) {
                $optID = $opt->getId();
            }
        }
        return $optID;
    }

    public function decorateArray($array, $prefix = 'decorated_', $forceSetAll = false)
    {
        return $this->arrayUtils->decorateArray($array, $prefix, $forceSetAll);
    }

    public function getSiteMainUrl()
    {
        return $this->_customizationHelper->getSiteMainUrl();
    }

    public function getCurrentCurrencyCode()
    {
        return $this->_customizationHelper->getCurrentCurrencyCode();
    }
}
