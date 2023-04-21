<?php
namespace Lordhair\Customizations\Block;
use Magento\Framework\View\Element\Template;
use Lordhair\Customizations\Helper\Data as CustomizationHelper;

class Highlight extends Template
{
    const STEP_TITLE = 'Highlight';
    const MIAN_ATTR_NAME = 'm_highlight_main';
    const STEP_ATTR_NAME = 'm_highlight';

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
            }
        }
        return $getSectedOptions;
    }

    public function _getSelectedInnerOptions()
    {
        $getSelectedOptions = $this->_getSelectedOptions();
        $getSelectedInnerOptions = array();
        $currentAttrName = '';
        foreach($getSelectedOptions as $key=>$innerOption){
            if ($innerOption['current']) {
                $currentAttrName = $key;
                $getSelectedInnerOptions = $innerOption;
                break;
            }
        }
        $getSelectedInnerOptions['attrName'] = $currentAttrName;
        return $getSelectedInnerOptions;
    }

    public function _getCurrentInnerOptions()
    {
        $getSelectedSessionOptions = $this->_customizationHelper->getSessionData('selectedOptions');
        $currentStepOption = array();
        foreach($getSelectedSessionOptions['steps'] as $key => $value){
            if ($value['current']) {
                $currentStepOption = $value['currentStepOption'];
                break;
            }
        }
        return $currentStepOption;
    }

    public function _getStepInnerOption($product) {
        $returnResult = array();
        $multipleOptions = array();
        $colorCodes = array();
        $getValuesMBaseSize = explode(",",$product->getResource()->getAttribute('m_highlights_type_new')->getFrontend()->getValue($product));
        foreach ($getValuesMBaseSize as $attribute) {
            $returnResult[] = array (
                'attrName' => 'm_highlights_type_new',
                'optionTitle' => $attribute,
                'value' => '',
                'multiple' => 0,
                'parentOptionName' => array(
                    'I want highlights',
                    'I want highlights to my hair'
                ),
            );
        }
        $getValuesMBaseSize = explode(",",$product->getResource()->getAttribute('m_color_code')->getFrontend()->getValue($product));
        foreach ($getValuesMBaseSize as $attribute) {
            $multipleOptions[] = array (
                'attrName' => 'm_color_code',
                'optionTitle' => $attribute,
                'value' => '',
                'multiple' => 0,
                'parentOptionName' => array(
                    ''
                ),
            );
            $colorCodes[] = trim($attribute);
        }
        $getValuesMBaseSize = explode(",",$product->getResource()->getAttribute('m_color_code_for_women')->getFrontend()->getValue($product));
        foreach ($getValuesMBaseSize as $attribute) {
            $multipleOptions[] = array (
                'attrName' => 'm_color_code_for_women',
                'optionTitle' => $attribute,
                'value' => '',
                'multiple' => 0,
                'parentOptionName' => array(
                    ''
                ),
            );
            $colorCodes[] = trim($attribute);
        }
        $returnResult[] = array (
            'attrName' => self::STEP_ATTR_NAME,
            'value' => '',
            'multiple' => 1,
            'multiOptions' => $multipleOptions,
            'parentOptionName' => array(
                'Evenly Blended',
                'Spot/Dot',
                'Root color'
            ),
        );
        $returnResult[] = array (
            'attrName' => '',
            'value' => '',
            'multiple' => 1,
            'multiOptions' => array (
                array(
                    'attrName' => 'Spot/Dot',
                    'type' => 'range',
                    'optionTitle' => 'Highlight Percentage Front',
                    'displayTitle' => '1. Front',
                    'min' => '0',
                    'max' => '100',
                    'step' => '5',
                    'value' => '0',
                ),
                array(
                    'attrName' => 'Spot/Dot',
                    'type' => 'range',
                    'optionTitle' => 'Highlight Percentage Top',
                    'displayTitle' => '2. Top',
                    'min' => '0',
                    'max' => '100',
                    'step' => '5',
                    'value' => '0',
                ),
                array(
                    'attrName' => 'Spot/Dot',
                    'type' => 'range',
                    'optionTitle' => 'Highlight Percentage Crown',
                    'displayTitle' => '3. Crown',
                    'min' => '0',
                    'max' => '100',
                    'step' => '5',
                    'value' => '0',
                ),
                array(
                    'attrName' => 'Spot/Dot',
                    'type' => 'range',
                    'optionTitle' => 'Highlight Percentage Back',
                    'displayTitle' => '4. Back',
                    'min' => '0',
                    'max' => '100',
                    'step' => '5',
                    'value' => '0',
                ),
                array(
                    'attrName' => 'Spot/Dot',
                    'type' => 'range',
                    'optionTitle' => 'Highlight Percentage Temples',
                    'displayTitle' => '5,6. Temples',
                    'min' => '0',
                    'max' => '100',
                    'step' => '5',
                    'value' => '0',
                ),
                array(
                    'attrName' => 'Spot/Dot',
                    'type' => 'range',
                    'optionTitle' => 'Highlight Percentage Sides',
                    'displayTitle' => '7,8. Sides',
                    'min' => '0',
                    'max' => '100',
                    'step' => '5',
                    'value' => '0',
                )
            ),
            'parentOptionName' => $colorCodes,
        );
        $returnResult[] = array (
            'attrName' => '',
            'optionTitle' => '',
            'value' => '',
            'multiple' => 0,
            'parentOptionName' => $colorCodes,
        );
        $returnResult[] = array (
            'attrName' => '',
            'optionTitle' => '',
            'value' => '',
            'multiple' => 0,
            'parentOptionName' => array(
                'Please refer to my specific instructions'
            ),
        );
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
                break;
            }
        }
        //create newSessionArray for add current option to session
        $newSessionArray = array();
        $isNeedToUnset = 0;
        $isUnsetHairPerc = 0;
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
                    $savSession = array (
                        'attrName' => '',
                        'optionTitle' => '',
                        'type' => 'range',
                        'current' => 1,
                        'multiple' => 1,
                        'priceRule' => '',
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
                    'No need highlights',
                    'Match the sample I\'ll send in',
                    'Match the sample already on file',
                    'Same as my last order',
                    'm_hair_color_special',
                    'm_highlights_type_new_special',
                    'm_highlight_percentage',
                    'm_hair_length_root',
                );
                $isLastOption = 0;
                $value['isCompleted'] = 0.5;
                if ($selectedPostCurrAttr && (in_array($selectedPostCurrAttr, $lastOptionArr) || in_array(trim($selectedPostOptionAttr), $lastOptionArr))) {
                    $savSession['isLastOption'] = 1;
                    $value['isCompleted'] = 1;
                    $this->setDoneOrNotStepToSession('full');
                    $isLastOption = 1;
                }
                if (count($value['options']) > 0) {
                    foreach ($value['options'] as $newKey => $newValue) {
                        if (isset($value['options'][$selectedPostCurrAttr])) {
                            $optionTitle = $value['options'][$selectedPostCurrAttr]['optionTitle'];
                            if (strcmp(trim($selectedPostOptionAttr), trim($optionTitle)) == 0) {
                                $isNeedToUnset = 1;
                            }
                            if (in_array(trim($selectedPostOptionAttr), $lastOptionArr)) {
                                $isUnsetHairPerc = 1;
                            }
                            break;
                        }
                    }
                    foreach ($value['options'] as $newKey => $newValue) {
                        $value['options'][$newKey]['current'] = 0;
                        if (isset($newValue['parentOptionName']) && $newValue['parentOptionName'] == $selectedPostCurrAttr && !$isNeedToUnset) {
                            unset($value['options'][$newKey]);
                        }
                        if ($newKey == 'm_highlight_percentage' && $isUnsetHairPerc) {
                            unset($value['options']['m_highlight_percentage']);
                        }
                        if ($newKey == 'm_hair_length_root' && $isUnsetHairPerc) {
                            unset($value['options']['m_hair_length_root']);
                        }
                        if ($newKey == 'm_color_code' && $isUnsetHairPerc) {
                            unset($value['options']['m_color_code']);
                        }
                        if ($selectedPostCurrAttr == 'm_color_code') {
                            unset($value['options']['m_color_code_for_women']);
                        }
                        if ($selectedPostCurrAttr == 'm_color_code_for_women') {
                            unset($value['options']['m_color_code']);
                        }
                    }
                }
                $value['options'][$selectedPostCurrAttr] = $savSession; 
                if (!$isLastOption) {
                    $currentStepOption = array (
                        'selectedCurrAttr' => $childOption['attrName'],
                        'selectedCurrentParent' => $selectedPostCurrAttr
                    );
                    $value['currentStepOption'] = $currentStepOption;
                }
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

    public function getChildOption($product, $selectedPostOptionAttr)
    {
        $setCurrentSetpSession = $this->_getStepInnerOption($product);
        $childOption = array();
        $childOption = array (
            'attrName' => '',
            'optionTitle' => '',
            'selected' => 0,
            'multiple' => 0,
            'isLastOption' => 1,
            'parentOptionName' => '',
            'childOption' => array()
        );
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
                        'parentOptionName' => '',
                        'childOption' => $option['multiOptions']
                    );

                } else {
                    $childOption = array (
                        'attrName' => $option['attrName'],
                        'optionTitle' => $option['optionTitle'],
                        'selected' => 0,
                        'multiple' => 0,
                        'isLastOption' => 0,
                        'parentOptionName' => '',
                        'childOption' => array()
                    );
                }
                break;
            }
        }
        return $childOption;
    }

    public function _getCurrentAttributesCall($product, $getPostParams)
    {
        $resultAttrArray = array();
        $getSelectedOptions = array();
        $attributeName = self::STEP_ATTR_NAME;
        $getPostOptionData = $getPostParams['optionData'];
        $getCurrentAttr = $getPostOptionData['currentAttr'];

        if ($getPostParams['isPrevClicked']) {

            $_getSessionSelectedOptions = $this->_customizationHelper->getSessionData('selectedOptions');
            $getCurrentInnerOptions = $this->_getCurrentInnerOptions();
            $attributeName = $getCurrentInnerOptions['selectedCurrentParent'];
            $newSessionArray = array();
            $selectedPostCurrentParent = '';
            foreach ($_getSessionSelectedOptions['steps'] as $key => $value) {
                if ($value['current']) {
                    if (count($value['options']) > 0) {
                        foreach ($value['options'] as $newKey => $newValue) {
                            if (count($value['options']) > 1 && $newKey == $attributeName) {
                                $value['options'][$newKey]['current'] = 1;
                            } else {
                                $value['options'][$newKey]['current'] = 0;
                            }

                            if ($newKey == $attributeName) {
                                $selectedPostCurrentParent = $newValue['parentOptionName'];
                                $getCurrentAttr = $newValue['parentOptionName'];
                            }
                        }
                        if(count($value['options']) == 1){
                            $value['options'][self::STEP_ATTR_NAME]['current'] = 1;
                        }
                    }
                    //set current option
                    $currentStepOption = array (
                        'selectedCurrAttr' => $attributeName,
                        'selectedCurrentParent' => $getCurrentAttr
                    );
                    $value['currentStepOption'] = $currentStepOption;

                    $newSessionArray[] = $value;
                }else{
                    $newSessionArray[] = $value;
                }
            }
            $_getSessionSelectedOptions['steps'] = $newSessionArray;
            $this->_customizationHelper->setSessionData('selectedOptions', $_getSessionSelectedOptions);

        } else if ($getPostOptionData['selectedOptionAttr'] && $getPostOptionData['selectedOptionAttr'] != '') {

            $getSelectedOptions = $this->_setInnerOptionToStep($product, $getPostParams);
            $attributeName = $getSelectedOptions['attrName'];
            $getCurrentAttr = $getSelectedOptions['parentOptionName'];

        } else {

            //set current to main option
            $this->_setMainOptionToCurrent();
            $getPostOptionData['selectedOptionAttr'] = '';
        }
        
        if ($attributeName != '') {
            if ($attributeName == 'm_color_code' || $attributeName == 'm_color_code_for_women') {
                $attribute_value = $this->getChildOption($product, 'Evenly Blended');
                $resultAttrArray = array(
                    'attributeName' => 'Evenly Blended',
                    'parentName' => $getCurrentAttr,
                    'mulipleOptions' => $attribute_value,
                    'attributValue' => ''
                );
            } else {
                $attribute_value = explode(",",$product->getResource()->getAttribute($attributeName)->getFrontend()->getValue($product));
                $resultAttrArray = array(
                    'attributeName' => $attributeName,
                    'parentName' => $getCurrentAttr,
                    'mulipleOptions' => '',
                    'attributValue' => $attribute_value
                );
            }
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

    public function setOptionLevelToSession($level) {
        $this->_customizationHelper->setSessionData('currentOptionLevel',$level);
    }

    public function setDoneOrNotStepToSession($level) {
        $this->_customizationHelper->setSessionData('doneOrNotStep',$level);
    }

    public function setSubTitleToSession($value) {
        $this->_customizationHelper->setSessionData('subTitle',$value);
    }

    public function _getPricesArray($product) {
        return $this->_customizationHelper->getOptionPriceArray($product);
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

    public function getImagesByAttrName($attrName) {
        $imagesArr = array (
            array (
                'attrName' => 'No need highlights',
                'attrImages' => array (
                    'mainImage' => 'ic_no_bleach_knots.png',
                    'thumbImage' => 'ic_no_bleach_knots.png',
                ),
            ),
            array (
                'attrName' => 'I want highlights',
                'attrImages' => array (
                    'mainImage' => 'img_i_want_highlights_my_hair.png',
                    'thumbImage' => 'img_i_want_highlights_my_hair.png',
                ),
            ),
            array (
                'attrName' => 'I want highlights to my hair',
                'attrImages' => array (
                    'mainImage' => 'img_i_want_highlights_my_hair.png',
                    'thumbImage' => 'img_i_want_highlights_my_hair.png',
                ),
            ),
            array (
                'attrName' => 'Match the sample I\'ll send in',
                'attrImages' => array (
                    'mainImage' => 'ic_old_unit.png',
                    'thumbImage' => 'ic_old_unit.png',
                ),
            ),
            array (
                'attrName' => 'Match the sample already on file',
                'attrImages' => array (
                    'mainImage' => 'ic_templates_and_samples.png',
                    'thumbImage' => 'ic_templates_and_samples.png',
                ),
            ),
            array (
                'attrName' => 'Same as my last order',
                'attrImages' => array (
                    'mainImage' => 'ic_same_as_my_last_order.png',
                    'thumbImage' => 'ic_same_as_my_last_order.png',
                ),
            ),
            array (
                'attrName' => 'Evenly Blended',
                'attrImages' => array (
                    'mainImage' => 'img_HL_evenly_blended_main.png',
                    'thumbImage' => 'img_HL_evenly_blended.png',
                ),
            ),
            array (
                'attrName' => 'Spot/Dot',
                'attrImages' => array (
                    'mainImage' => 'img_HL_spot_dot_main.png',
                    'thumbImage' => 'img_HL_spot_dot.png',
                ),
            ),
            array (
                'attrName' => 'Highlight Percentage',
                'attrImages' => array (
                    'mainImage' => 'Highlight_colour_Percentage.png',
                    'thumbImage' => 'Highlight_colour_Percentage.png',
                ),
            ),
            array (
                'attrName' => 'Please refer to my specific instructions',
                'attrImages' => array (
                    'mainImage' => 'ic_my_specific_instructions.png',
                    'thumbImage' => 'ic_my_specific_instructions.png',
                ),
            ),
            array (
                'attrName' => 'Root color',
                'attrImages' => array (
                    'mainImage' => 'img_HL_root_color_main.png',
                    'thumbImage' => 'img_HL_root_color.png',
                ),
            )
        );
        $returnResult = array (
            'mainImage' => 'hair.png',
            'thumbImage' => 'hair.png',
        );
        $noMatchflag = true;
        foreach ($imagesArr as $singleImage) {
            if ($singleImage['attrName'] == trim($attrName)) {
                $returnResult = $singleImage['attrImages'];
                $noMatchflag = false;
                break;
            }
        }
        if($noMatchflag){
            $colorArr  = explode('#', trim($attrName));
            $colorName = $colorArr[0];
            if( count($colorArr) > 1 ){
                if(strpos($attrName, 'Womens') > 0){
                    return array('mainImage' => $colorName.'hc-women_big.jpg', 'thumbImage' => $colorName.'hc-women.jpg',);
                }else{
                    return array('mainImage' => $colorName.'hc-men_big.jpg', 'thumbImage' => $colorName.'hc.jpg',);
                }                
            }
        }
        return $returnResult;
    }
}