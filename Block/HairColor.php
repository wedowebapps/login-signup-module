<?php
namespace Lordhair\Customizations\Block;
use Magento\Framework\View\Element\Template;
use Lordhair\Customizations\Helper\Data as CustomizationHelper;

class HairColor extends Template
{
    const STEP_TITLE = 'Hair Color';
    const MIAN_ATTR_NAME = 'm_hair_color_main';
    const STEP_ATTR_NAME = 'm_hair_color';

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
        $getValuesMBaseSize = explode(",",$product->getResource()->getAttribute('m_color_code')->getFrontend()->getValue($product));
        foreach ($getValuesMBaseSize as $attribute) {
            $multipleOptions[] = array (
                'attrName' => 'm_color_code',
                'optionTitle' => $attribute,
                'value' => '',
                'multiple' => 0,
                'parentOptionName' => array(
                    'Use your color code'
                ),
            );
        }
        $getValuesMBaseSize = explode(",",$product->getResource()->getAttribute('m_color_code_for_women')->getFrontend()->getValue($product));
        foreach ($getValuesMBaseSize as $attribute) {
            $multipleOptions[] = array (
                'attrName' => 'm_color_code_for_women',
                'optionTitle' => $attribute,
                'value' => '',
                'multiple' => 0,
                'parentOptionName' => array(
                    'Use your color code'
                ),
            );
        }
        $returnResult[] = array (
            'attrName' => self::STEP_ATTR_NAME,
            'value' => '',
            'multiple' => 1,
            'multiOptions' => $multipleOptions,
            'parentOptionName' => array(
                'Use your color code'
            ),
        );
        $returnResult[] = array (
            'attrName' => '',
            'optionTitle' => '',
            'value' => '',
            'multiple' => 0,
            'parentOptionName' => array(
                'Please refer to my special instructions'
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
                    'Match the sample I\'ll send in(Recommended)',
                    'I\'ll send in an old system as sample',
                    'Use my sample already on file',
                    'm_color_code',
                    'm_color_code_for_women',
                    'm_hair_color_special'
                );
                $value['isCompleted'] = 0.5;
                if ($selectedPostCurrAttr && (in_array($selectedPostCurrAttr, $lastOptionArr) || in_array(trim($selectedPostOptionAttr), $lastOptionArr))) {
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
                            break;
                        }
                    }
                    foreach ($value['options'] as $newKey => $newValue) {
                        $value['options'][$newKey]['current'] = 0;
                        if (isset($newValue['parentOptionName']) && $newValue['parentOptionName'] == $selectedPostCurrAttr && !$isNeedToUnset) {
                            unset($value['options'][$newKey]);
                        }
                    }
                }
                if ($selectedPostCurrAttr == 'm_color_code_for_women' && isset($value['options']['m_color_code'])) {
                    unset($value['options']['m_color_code']);
                }else if ($selectedPostCurrAttr == 'm_color_code' && isset($value['options']['m_color_code_for_women'])) {
                    unset($value['options']['m_color_code_for_women']);
                }
                $value['options'][$selectedPostCurrAttr] = $savSession;
                if ($getPostOptionData['optionLevel'] != '3_2' && $getPostOptionData['optionLevel'] != '4_2') {
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

    public function _checkAlreadyInSession($attributeName,$attribute)
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
                if (isset($item[1]) && strcmp(trim($item[1]), trim($newattribute)) == 0) {
                    $activeAttrOption = $attribute;
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

    public function setOptionLevelToSession($level) {
        $this->_customizationHelper->setSessionData('currentOptionLevel',$level);
    }

    public function setDoneOrNotStepToSession($level) {
        $this->_customizationHelper->setSessionData('doneOrNotStep',$level);
    }

    public function setSubTitleToSession($value) {
        $this->_customizationHelper->setSessionData('subTitle',$value);
    }

    public function getImagesByAttrName($attrName) {
        $imagesArr = array (
            array (
                'attrName' => 'Match the sample I\'ll send in(Recommended)',
                'attrImages' => array (
                    'mainImage' => 'hair.png',
                    'thumbImage' => 'hair.png',
                ),
            ),
            array (
                'attrName' => 'I\'ll send in an old system as sample',
                'attrImages' => array (
                    'mainImage' => 'front-cont-img2.png',
                    'thumbImage' => 'front-cont-img2.png',
                ),
            ),
            array (
                'attrName' => 'Use my sample already on file',
                'attrImages' => array (
                    'mainImage' => 'ic_use_my_sample.png',
                    'thumbImage' => 'ic_use_my_sample.png',
                ),
            ),
            array (
                'attrName' => 'Use your color code',
                'attrImages' => array (
                    'mainImage' => 'color-code.png',
                    'thumbImage' => 'color-code.png',
                ),
            ),
            array (
                'attrName' => 'Please refer to my special instructions',
                'attrImages' => array (
                    'mainImage' => 'envelop.png',
                    'thumbImage' => 'envelop.png',
                ),
            )
        );
        $noMatchflag = true;
        $returnResult = array (
            'mainImage' => 'hair.png',
            'thumbImage' => 'hair.png',
        );
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
