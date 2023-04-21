<?php
namespace Lordhair\Customizations\Block;
use Magento\Framework\View\Element\Template;
use Lordhair\Customizations\Helper\Data as CustomizationHelper;

class HairLength extends Template
{
    const STEP_TITLE = 'Hair length';
    const MIAN_ATTR_NAME = 'm_hair_length';

    /**
     * @var Magento\Framework\App\Request\Http $request
     */
    protected $request;

    /**
     * @var Lordhair\Customizations\Helper\Data $CustomizationHelper
     */
    protected $_customizationHelper;

    public function __construct(
        Template\Context $context,
        \Magento\Framework\App\Request\Http $request,
        CustomizationHelper $customizationHelper,
        array $data = []
    ) {
        $this->_isScopePrivate = true;
        $this->request = $request;
        $this->_customizationHelper = $customizationHelper;
        parent::__construct($context, $data);
    }

    protected function _prepareLayout()
    {
        return parent::_prepareLayout();
    }

    public function getSiteMainUrl()
    {
        return $this->_customizationHelper->getSiteMainUrl();
    }

    public function _getSelectedOptions()
    {
        $getSelectedSessionOptions = $this->_customizationHelper->getSessionData('selectedOptions');
        $getSectedOptions = array();
        foreach($getSelectedSessionOptions['steps'] as $step){
            if ($step['current']) {
                $getSectedOptions = $step['options'];
                break;
            }
        }
        return $getSectedOptions;
    }

    public function _getPricesArray($product) {
        return $this->_customizationHelper->getOptionPriceArray($product);
    }

    public function _getStepInnerOption($product) {
        $returnResult = array();
        return $returnResult;
    }

    public function _setInnerOptionToStep($product, $getPostParams) 
    {
        $_getSessionSelectedOptions = $this->_customizationHelper->getSessionData('selectedOptions');
        $setCurrentSetpSession = $this->_getStepInnerOption($product);
        $childOption = array();
        $getPostOptionData = $getPostParams['optionData'];
        $selectedPostOptionAttr = $getPostOptionData['selectedOptionAttr'];
        $selectedPostCurrAttr = $getPostOptionData['currentAttr'];
        $selectedPostCurrentParent = $getPostOptionData['parentAttr'];
        $childOption = array (
            'attrName' => '',
            'optionTitle' => '',
            'selected' => 0,
            'multiple' => 0,
            'isLastOption' => 1,
            'parentOptionName' => '',
            'childOption' => array()
        );
        //create childOption for return next html
        foreach ($setCurrentSetpSession as $option) {
            if (in_array(trim($selectedPostOptionAttr), $option['parentOptionName']))
            {
                if ($option['multiple']) {

                    $childOption = array (
                        'attrName' => '',
                        'optionTitle' => '',
                        'selected' => 0,
                        'multiple' => 1,
                        'isLastOption' => 0,
                        'parentOptionName' => $selectedPostCurrAttr,
                        'childOption' => $option['multiOptions']
                    );

                } else {
                    $childOption = array (
                        'attrName' => $option['attrName'],
                        'optionTitle' => $option['optionTitle'],
                        'selected' => 0,
                        'multiple' => 0,
                        'isLastOption' => 0,
                        'parentOptionName' => $selectedPostCurrAttr,
                        'childOption' => array()
                    );
                }
            }
        }
        //create newSessionArray for add current option to session
        $newSessionArray = array();
        $isNeedToUnset = 0;
        foreach ($_getSessionSelectedOptions['steps'] as $key => $value) {
            if ($value['current']) {
                if ($getPostOptionData['optionType'] == 'multiple') {
                    $savSession = array (
                        'attrName' => '',
                        'optionTitle' => '',
                        'type' => 'multiple',
                        'current' => 0,
                        'multiple' => 1,
                        'priceRule' => '',
                        'parentOptionName' => $selectedPostCurrentParent,
                        'childOption' => $getPostOptionData['values']
                    );
                }else if ($getPostOptionData['optionType'] == 'range') {
                    $getPriceRule = $this->getPriceRuleRange($product,$getPostOptionData,true);
                    $savSession = array (
                        'attrName' => '',
                        'optionTitle' => $getPriceRule['attrTitle'],
                        'type' => 'range',
                        'current' => 1,
                        'multiple' => 1,
                        'priceRule' => $getPriceRule['priceRule'],
                        'parentOptionName' => $selectedPostCurrentParent,
                        'childOption' => $getPostOptionData['values']
                    );
                } else {
                    $getPriceRule = $this->getPriceRule($product,$selectedPostOptionAttr,true);
                    $savSession = array (
                        'attrName' => '',
                        'optionTitle' => $selectedPostOptionAttr,
                        'type' => 'checkbox',
                        'current' => 1,
                        'multiple' => 0,
                        'priceRule' => $getPriceRule,
                        'parentOptionName' => $selectedPostCurrentParent,
                        'childOption' => array()
                    );
                }
                $lastOptionArr = array(
                    'm_hair_length'
                );
                $value['isCompleted'] = 0.5;
                if ($selectedPostCurrAttr && in_array($selectedPostCurrAttr, $lastOptionArr)) {
                    $savSession['isLastOption'] = 1;
                    $value['isCompleted'] = 1;
                    $this->setDoneOrNotStepToSession('full');
                }
                if (count($value['options']) > 0) {
                    foreach ($value['options'] as $newKey => $newValue) {
                        if (isset($value['options'][$selectedPostCurrAttr])) {
                            $optionTitle = $value['options'][$selectedPostCurrAttr]['optionTitle'];
                            if (strcmp(trim($selectedPostOptionAttr), trim($optionTitle)) == 0) {
                                $isNeedToUnset = 1;
                            }
                        }
                    }
                    foreach ($value['options'] as $newKey => $newValue) {
                        $value['options'][$newKey]['current'] = 0;
                        if (isset($newValue['parentOptionName']) && $newValue['parentOptionName'] == $selectedPostCurrAttr && !$isNeedToUnset) {
                            unset($value['options'][$newKey]);
                        }
                    }
                }
                $value['options'][$selectedPostCurrAttr] = $savSession;
                $currentStepOption = array (
                    'selectedCurrAttr' => $childOption['attrName'],
                    'selectedCurrentParent' => $selectedPostCurrAttr
                );
                $value['currentStepOption'] = $currentStepOption;
                $newSessionArray[] = $value;
            }else{
                $newSessionArray[] = $value;
            }
        }
        $_getSessionSelectedOptions['steps'] = $newSessionArray;
        $this->_customizationHelper->setSessionData('selectedOptions', $_getSessionSelectedOptions);
        return $childOption;
    }

    public function _setMainOptionToCurrent()
    {
        $_getSessionSelectedOptions = $this->_customizationHelper->getSessionData('selectedOptions');
        $newSessionArray = array();
        foreach ($_getSessionSelectedOptions['steps'] as $value) {
            
            if ($value['current']) {
                foreach($value['options'] as $newKey=>$newValue) {
                    $value['options'][$newKey]['current'] = 0;
                    if ($newValue['parentOptionName'] == self::MIAN_ATTR_NAME) {
                        $value['options'][$newKey]['current'] = 1;
                    }
                }
                $newSessionArray[] = $value;
            } else {
                $newSessionArray[] = $value;
            }
        }
        $_getSessionSelectedOptions['steps'] = $newSessionArray;
        $this->_customizationHelper->setSessionData('selectedOptions', $_getSessionSelectedOptions);
    }

    public function _checkAlreadyInSession($attributeName)
    {
        $getSelectedInnerOptions = $this->_getSelectedOptions();
        $activeAttrOption = '';
        foreach($getSelectedInnerOptions as $key=>$value){
            if (strcmp(trim($key), trim($attributeName)) == 0) {
                if ($value['type'] == 'range') {
                    $activeAttrOption = $value['childOption'];
                } else {
                    $activeAttrOption = $value['optionTitle'];
                }
                break;
            }
        }
        $activeAttrOption = str_replace('&comma;', ',', $activeAttrOption);
        return $activeAttrOption;
    }

    public function _checkAlreadyInSessionSlide($attributeName,$attribute)
    {
        $getSelectedInnerOptions = $this->_getSelectedOptions();
        $activeAttrOption = '';
        foreach($getSelectedInnerOptions as $key=>$value){
            if (strcmp(trim($key), trim($attributeName)) == 0) {
                if ($value['type'] == 'range') {
                    $activeAttrOption = $value['childOption'];
                } else {
                    $activeAttrOption = $value['optionTitle'];
                }
                break;
            }
        }
        $preSelectd = $this->_customizationHelper->getSessionData('setPreSelected');
        if ($preSelectd && count($preSelectd) > 0 && $activeAttrOption == '') {
            $newattribute = str_replace('&amp;', '&', $attribute);
            $newattribute = str_replace('&comma;', ',', $newattribute);
            foreach($preSelectd as $item) {
                if (strpos(trim($item[0]), trim($newattribute)) !== false) {
                    $activeAttrOption = $item[1];
                    break;
                }
            }
        }
        if (!is_array($activeAttrOption)) {
            $activeAttrOption = str_replace('spchar39', "'", $activeAttrOption);
            $activeAttrOption = str_replace('&comma;', ',', $activeAttrOption);
        }
        return $activeAttrOption;
    }

    public function getPriceRule($product,$optionTitle,$isRemoveComma = false)
    {
        $prices = $this->_customizationHelper->getOptionPriceArray($product);
        $optionPrice = '';
        $filterOptionTitle = str_replace('&comma;', ',', trim($optionTitle));
        if(array_key_exists($filterOptionTitle,$prices))
        {
            $optionPrice = $prices[$filterOptionTitle];
        }
        if ($isRemoveComma){
            $optionPrice = str_replace(',', '', $optionPrice);
        }
        return $optionPrice;
    }

    public function getFrmtdPriceRule($getPriceRule)
    {
        return $this->_customizationHelper->getFrmtdPriceRule($getPriceRule,true);
    }

    public function _getCurrentAttributesCall($product, $getPostParams)
    {
        $_getSessionSelectedOptions = $this->_customizationHelper->getSessionData('selectedOptions');
        $resultAttrArray = array();
        $getSelectedOptions = array();
        $attributeName = self::MIAN_ATTR_NAME;
        $getPostOptionData = $getPostParams['optionData'];
        $getCurrentAttr = $getPostOptionData['currentAttr'];

        if ($getPostOptionData['selectedOptionAttr'] && $getPostOptionData['selectedOptionAttr'] != '') {

            $getSelectedOptions = $this->_setInnerOptionToStep($product, $getPostParams);
            $attributeName = $getSelectedOptions['attrName'];
            $getCurrentAttr = $getSelectedOptions['parentOptionName'];

        } else {

            //set current to main option
            $this->_setMainOptionToCurrent();
            $getPostOptionData['selectedOptionAttr'] = '';
        }

        if ($attributeName != '') {
            $attribute_value = explode(",",$product->getResource()->getAttribute($attributeName)->getFrontend()->getValue($product));
            $resultAttrArray = array(
                'attributeName' => $attributeName,
                'parentName' => $getCurrentAttr,
                'mulipleOptions' => '',
                'attributValue' => $attribute_value
            );
        } else {
            if (isset($getSelectedOptions['isLastOption']) && $getSelectedOptions['isLastOption']) {
                $resultAttrArray = array(
                    'attributeName' => '',
                    'parentName' => $getCurrentAttr,
                    'mulipleOptions' => '',
                    'attributValue' => ''
                );
            } else {
                $resultAttrArray = array(
                    'attributeName' => $getPostOptionData['selectedOptionAttr'],
                    'parentName' => $getCurrentAttr,
                    'mulipleOptions' => $getSelectedOptions,
                    'attributValue' => ''
                );
            }
        }

        return $resultAttrArray;
    }

    public function getImagesByAttrName($attrName) {
        $imagesArr = array (
            array (
                'attrName' => 'Length',
                'attrImages' => array (
                    'mainImage' => 'hairlength.png',
                    'thumbImage' => 'hairlength.png',
                ),
            )
        );
        $returnResult = array (
            'mainImage' => 'hair.png',
            'thumbImage' => 'hair.png',
        );
        foreach ($imagesArr as $singleImage) {
            if ($singleImage['attrName'] == trim($attrName)) {
                $returnResult = $singleImage['attrImages'];
                break;
            }
        }
        return $returnResult;
    }

    public function setOptionLevelToSession($level) {
        $this->_customizationHelper->setSessionData('currentOptionLevel',$level);
    }

    public function setDoneOrNotStepToSession($level) {
        $this->_customizationHelper->setSessionData('doneOrNotStep',$level);
    }

    public function setSubTitleToSession($value) {
        $this->_customizationHelper->setSessionData('subTitle',$value);
    }

    public function getPriceRuleRange($product, $postOptionData,$isRemoveComma = false) {
        $returnArray = array();
        $returnPriceRule = '';
        $attrTitle = '';
        if ($postOptionData['currentAttr'] == self::MIAN_ATTR_NAME) {
            $attribute_value = explode(",",$product->getResource()->getAttribute(self::MIAN_ATTR_NAME)->getFrontend()->getValue($product));
            $prices = $this->_customizationHelper->getOptionPriceArray($product);
            $optionPriceArray = array();
            $optionTitleArr = array();
            foreach ($attribute_value as $attribute) {
                if(array_key_exists(trim($attribute),$prices)) {
                    preg_match_all('!\d+!', $attribute, $matches);
                    $optionPriceArray[$matches[0][0]] = $prices[trim($attribute)];
                    $optionTitleArr[$matches[0][0]] = trim($attribute);
                }
            }
            foreach($postOptionData['values'] as $value) {
                $sliderValue = $value['sliderValue'];
                if (isset($optionPriceArray[$sliderValue])) {
                    $returnPriceRule = $optionPriceArray[$sliderValue];
                    $attrTitle = $optionTitleArr[$sliderValue];
                }
            }
        }
        if ($isRemoveComma){
            $returnPriceRule = str_replace(',', '', $returnPriceRule);
        }
        $returnArray = array(
            'priceRule' => $returnPriceRule,
            'attrTitle' => $attrTitle
        );
        return $returnArray;
    }
}