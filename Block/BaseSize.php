<?php
namespace Lordhair\Customizations\Block;
use Magento\Framework\View\Element\Template;
use Lordhair\Customizations\Helper\Data as CustomizationHelper;

class BaseSize extends Template
{

    const STEP_TITLE = 'Base Size';
    const MIAN_ATTR_NAME = 'base_size_main';
    const MIAN_ATTR = 'm_template_samples';

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
            if (strcmp(trim($step['attribute_value']), trim(self::MIAN_ATTR)) == 0){
                $getSectedOptions = $step['options'];
                break;
            }
        }
        return $getSectedOptions;
    }

    public function _getSelectedInnerOptions()
    {
        $getSelectedOptions = $this->_getSelectedOptions();
        $getSelectedInnerOptions = array();
        if (count($getSelectedOptions) > 0) {
            foreach($getSelectedOptions as $key=>$innerOption){
                if ($innerOption['current']) {
                    $getSelectedInnerOptions = $innerOption;
                    $getSelectedInnerOptions['attrName'] = $key;
                    break;
                }
            }
        }
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

    public function getImagesByAttrName($attrName) {
        $imagesArr = array (
            array (
                'attrName' => 'I\'ll send in an old unit for size and curvature reference',
                'attrImages' => array (
                    'mainImage' => 'ic_old_unit.png',
                    'thumbImage' => 'ic_old_unit.png',
                ),
            ),
            array (
                'attrName' => 'I\'ll send in a new template for size and curvature reference',
                'attrImages' => array (
                    'mainImage' => 'ic_old_unit.png',
                    'thumbImage' => 'ic_old_unit.png',
                ),
            ),
            array (
                'attrName' => 'Use my template and samples already on file',
                'attrImages' => array (
                    'mainImage' => 'ic_templates_and_samples.png',
                    'thumbImage' => 'ic_templates_and_samples.png',
                ),
            ),
            array (
                'attrName' => 'I\'ll fill the dimension in below Base Size section',
                'attrImages' => array (
                    'mainImage' => 'ic_dimension.png',
                    'thumbImage' => 'ic_dimension.png',
                ),
            ),
            array (
                'attrName' => 'Partial (size ≤ 7"x10"&comma; or area ≤ 70 square inches)',
                'attrImages' => array (
                    'mainImage' => 'Base-size-partial-big.jpg',
                    'thumbImage' => 'Base-size-partial.jpg',
                ),
            ),
            array (
                'attrName' => 'Regular (7"x10" < size ≤ 8"x10"&comma; or 70 square inches < area ≤ 80 square inches)',
                'attrImages' => array (
                    'mainImage' => 'Base-size-regular-big.jpg',
                    'thumbImage' => 'Base-size-regular.jpg',
                ),
            ),
            array (
                'attrName' => 'Oversize (8"x10" < size ≤ 10"x10"&comma; or 80 square inches < area ≤ 100 square inches)',
                'attrImages' => array (
                    'mainImage' => 'Base-size-over-size-big.jpg',
                    'thumbImage' => 'Base-size-over-size.jpg',
                ),
            ),
            array (
                'attrName' => 'Full cap (size > 10"x10"&comma; or area > 100 square inches)',
                'attrImages' => array (
                    'mainImage' => 'full-cap-big.jpg',
                    'thumbImage' => 'full-cap.jpg',
                ),
            ),
            array (
                'attrName' => 'Width',
                'attrImages' => array (
                    'mainImage' => 'ic_temple_temple.png',
                    'thumbImage' => 'ic_temple_temple.png',
                ),
            ),
            array (
                'attrName' => 'Length',
                'attrImages' => array (
                    'mainImage' => 'ic_base_length.png',
                    'thumbImage' => 'ic_base_length.png',
                ),
            ),
            array (
                'attrName' => 'Circumference',
                'attrImages' => array (
                    'mainImage' => 'ic_Circumference.png',
                    'thumbImage' => 'ic_Circumference.png',
                ),
            ),
            array (
                'attrName' => 'Front to nape',
                'attrImages' => array (
                    'mainImage' => 'ic_base_length.png',
                    'thumbImage' => 'ic_base_length.png',
                ),
            ),
            array (
                'attrName' => 'Ear to ear across forehead',
                'attrImages' => array (
                    'mainImage' => 'ic_ear_ear_across_forehead.png',
                    'thumbImage' => 'ic_ear_ear_across_forehead.png',
                ),
            ),
            array (
                'attrName' => 'Temple to temple',
                'attrImages' => array (
                    'mainImage' => 'ic_temple_temple.png',
                    'thumbImage' => 'ic_temple_temple.png',
                ),
            ),
            array (
                'attrName' => 'Ear to ear over top',
                'attrImages' => array (
                    'mainImage' => 'ic_Ear_ear_over_top.png',
                    'thumbImage' => 'ic_Ear_ear_over_top.png',
                ),
            ),
            array (
                'attrName' => 'Temple to temple round back',
                'attrImages' => array (
                    'mainImage' => 'ic_temple_temple_round_back.png',
                    'thumbImage' => 'ic_temple_temple_round_back.png',
                ),
            ),
            array (
                'attrName' => 'Nape of neck',
                'attrImages' => array (
                    'mainImage' => 'ic_Nape_neck.png',
                    'thumbImage' => 'ic_Nape_neck.png',
                ),
            ),
            array (
                'attrName' => 'Full Cap',
                'attrImages' => array (
                    'mainImage' => 'full-cap-big.jpg',
                    'thumbImage' => 'full-cap.jpg',
                ),
            ),
            array (
                'attrName' => 'Topper',
                'attrImages' => array (
                    'mainImage' => 'base-img-7.png',
                    'thumbImage' => 'base-img-7.png',
                ),
            )
        );
        $returnResult = array (
            'mainImage' => 'frontal-big.jpg',
            'thumbImage' => 'frontal-option.jpg',
        );
        foreach ($imagesArr as $singleImage) {
            if (strcmp(trim($singleImage['attrName']), trim($attrName)) == 0) {
                $returnResult = $singleImage['attrImages'];
                break;
            }
        }
        return $returnResult;
    }

    public function _getStepInnerOption($product) {
        $returnResult = array();
        $getValuesMBaseSize = explode(",",$product->getResource()->getAttribute('m_base_size')->getFrontend()->getValue($product));
        foreach ($getValuesMBaseSize as $attribute) {
            $returnResult[] = array (
                'attrName' => 'm_base_size',
                'optionTitle' => $attribute,
                'value' => '',
                'multiple' => 0,
                'parentOptionName' => array(
                    'I\'ll send in an old unit for size and curvature reference',
                    'I\'ll send in a new template for size and curvature reference',
                    'Use my template and samples already on file'
                ),
            );
        }
        $getValuesMBaseSizeSection = explode(",",$product->getResource()->getAttribute('m_base_size_section')->getFrontend()->getValue($product));
        foreach ($getValuesMBaseSizeSection as $attribute) {
            $returnResult[] = array (
                'attrName' => 'm_base_size_section',
                'optionTitle' => $attribute,
                'value' => '',
                'multiple' => 0,
                'parentOptionName' => array(
                    'I\'ll fill the dimension in below Base Size section',
                ),
            );
        }
        $returnResult[] = array (
            'attrName' => 'm_base_size_selection_topper',
            'value' => '',
            'multiple' => 1,
            'multiOptions' => array (
                array(
                    'attrName' => 'Topper',
                    'type' => 'range',
                    'optionTitle' => 'Width',
                    'min' => '0',
                    'max' => '14',
                    'step' => '0.125',
                    'value' => '0',
                ),
                array(
                    'attrName' => 'Topper',
                    'type' => 'range',
                    'optionTitle' => 'Length',
                    'min' => '0',
                    'max' => '14',
                    'step' => '0.125',
                    'value' => '0',
                )
            ),
            'parentOptionName' => array(
                'Topper',
            ),
        );
        $returnResult[] = array (
            'attrName' => 'm_base_size_section',
            'value' => '',
            'multiple' => 1,
            'multiOptions' => array (
                array(
                    'attrName' => 'Full Cap',
                    'type' => 'range',
                    'optionTitle' => 'Circumference',
                    'min' => '18',
                    'max' => '25',
                    'step' => '0.125',
                    'value' => '18',
                ),
                array(
                    'attrName' => 'Full Cap',
                    'type' => 'range',
                    'optionTitle' => 'Front to nape',
                    'min' => '10',
                    'max' => '15',
                    'step' => '0.125',
                    'value' => '10',
                ),
                array(
                    'attrName' => 'Full Cap',
                    'type' => 'range',
                    'optionTitle' => 'Ear to ear across forehead',
                    'min' => '9',
                    'max' => '15',
                    'step' => '0.125',
                    'value' => '9',
                ),
                array(
                    'attrName' => 'Full Cap',
                    'type' => 'range',
                    'optionTitle' => 'Temple to temple',
                    'min' => '10',
                    'max' => '19',
                    'step' => '0.125',
                    'value' => '10',
                ),
                array(
                    'attrName' => 'Full Cap',
                    'type' => 'range',
                    'optionTitle' => 'Ear to ear over top',
                    'min' => '10',
                    'max' => '16',
                    'step' => '0.125',
                    'value' => '10',
                ),
                array(
                    'attrName' => 'Full Cap',
                    'type' => 'range',
                    'optionTitle' => 'Temple to temple round back',
                    'min' => '2',
                    'max' => '20',
                    'step' => '0.125',
                    'value' => '2',
                ),
                array(
                    'attrName' => 'Full Cap',
                    'type' => 'range',
                    'optionTitle' => 'Nape of neck',
                    'min' => '2',
                    'max' => '7',
                    'step' => '0.125',
                    'value' => '2',
                )
            ),
            'parentOptionName' => array(
                'Full Cap',
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
                    'm_base_size',
                    'm_base_size_section_full_cap',
                    'm_base_size_selection_topper'
                );
                $value['isCompleted'] = 0.5;
                if ($selectedPostCurrAttr && in_array($selectedPostCurrAttr, $lastOptionArr)) {
                    $savSession['isLastOption'] = 1;
                    $value['isCompleted'] = 1;
                    $this->setDoneOrNotStepToSession('full');
                }
                
                if (count($value['options']) > 0) {
                    $checkBaseSizeExist = 0;
                    //check if m_base_size exist then topper and fullcap need to remove
                    if($selectedPostCurrAttr == 'm_base_size') {
                        $checkBaseSizeExist = 1;
                    }
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
                        if ($checkBaseSizeExist) {
                            unset($value['options']['m_base_size_section_full_cap']);
                            unset($value['options']['m_base_size_selection_topper']);
                        }
                    }
                }
                $value['options'][$selectedPostCurrAttr] = $savSession;
                //save current option attribute session
                if ($getPostOptionData['optionLevel'] != '123_2' && $getPostOptionData['optionLevel'] != '4_3') {
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
                    if ($newValue['parentOptionName'] && $newValue['parentOptionName'] == self::MIAN_ATTR_NAME) {
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
        $activeAttrOption = str_replace('spchar39', "'", $activeAttrOption);
        $activeAttrOption = str_replace('&comma;', ',', $activeAttrOption);
        return $activeAttrOption;
    }

    public function getPriceRule($product,$optionTitle,$isRemoveComma = false)
    {
        $prices = $this->_customizationHelper->getOptionPriceArray($product);
        $optionPrice = '';
        $filterOptionTitle = str_replace('&comma;', ',', trim($optionTitle));
        $optionTitle = str_replace('&gt;', '>', $optionTitle);
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
        return $this->_customizationHelper->getFrmtdPriceRule($getPriceRule);
    }

    public function getPriceRuleTopper($product)
    {
        $prices = $this->_customizationHelper->getOptionPriceArray($product);
        $sub_attribute_value = explode(",",$product->getResource()->getAttribute('m_base_size')->getFrontend()->getValue($product));
        $optionPriceArray = '';
        foreach($sub_attribute_value as $subattrib)
        {
            $filterOptionTitle = str_replace('&comma;', ',', trim($subattrib));
            if(array_key_exists(trim($filterOptionTitle),$prices))
            {
                $optionPriceArray.=$prices[trim($filterOptionTitle)].",";
                break;
            }
        }
        return $optionPriceArray;
    }

    public function _getCurrentAttributesCall($product, $getPostParams)
    {
        $resultAttrArray = array();
        $getSelectedOptions = array();
        $attributeName = "m_template_samples";
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
                            $value['options']['m_template_samples']['current'] = 1;
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
                'mulipleOptions' => '',
                'attributValue' => $attribute_value
            );
        } else {
            if (isset($getSelectedOptions['isLastOption']) && $getSelectedOptions['isLastOption']) {
                $resultAttrArray = array(
                    'attributeName' => '',
                    'mulipleOptions' => '',
                    'attributValue' => ''
                );
            } else {
                $resultAttrArray = array(
                    'attributeName' => $getPostOptionData['selectedOptionAttr'],
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

    public function getPriceRuleRange($product,$postOptionData,$isRemoveComma = false) {
        $returnPriceRule = '';
        $basesizeValue = array('Partial (size ≤ 7"x10", or area ≤ 70 square inches)','Regular (7"x10" < size ≤ 8"x10", or 70 square inches < area ≤ 80 square inches)','Oversize (8"x10" < size ≤ 10"x10", or 80 square inches < area ≤ 100 square inches)','Full cap (size > 10"x10", or area > 100 square inches)');
        $attrTitle = '';
        if ($postOptionData['currentAttr'] == 'm_base_size_section_full_cap') {
            $attrTitle = $basesizeValue[3];
            $returnPriceRule = $this->getPriceRule($product,$attrTitle);
        } else if ($postOptionData['currentAttr'] == 'm_base_size_selection_topper') {
            $returnPriceRule = array(1);
            foreach($postOptionData['values'] as $value) {
                $returnPriceRule[] = $value['sliderValue'];
            }
            $returnPriceRule = array_product($returnPriceRule);
            switch (true) {
                case $returnPriceRule <= 70:
                    $returnPriceRule = '+0.00';
                break;
                case $returnPriceRule > 70 && $returnPriceRule <= 80:
                    $attrTitle = $basesizeValue[1];
                    $returnPriceRule = $this->getPriceRule($product,$attrTitle);
                break;
                case $returnPriceRule > 80 && $returnPriceRule <= 100:
                    $attrTitle = $basesizeValue[2];
                    $returnPriceRule = $this->getPriceRule($product,$attrTitle);
                break;
                case $returnPriceRule > 100:
                    $attrTitle = $basesizeValue[3];
                    $returnPriceRule = $this->getPriceRule($product,$attrTitle);
                break;
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