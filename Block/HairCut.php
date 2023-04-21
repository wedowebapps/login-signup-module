<?php
namespace Lordhair\Customizations\Block;
use Magento\Framework\View\Element\Template;
use Magento\Framework\Session\SessionManagerInterface;
use Lordhair\Customizations\Helper\Data as CustomizationHelper;

class HairCut extends Template
{
    const STEP_TITLE = 'Hair Cut';
    const MIAN_ATTR_NAME = 'm_cut_in_and_style_main';
    const STEP_ATTR_NAME = 'm_cut_in_and_style';

    /**
     * @var Magento\Framework\App\Request\Http $request
     */
    protected $request;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface $_coreSession
     */
    protected $_coreSession;

    /**
     * @var Lordhair\Customizations\Helper\Data $CustomizationHelper
     */
    protected $_customizationHelper;

    public function __construct(
        Template\Context $context,
        \Magento\Framework\App\Request\Http $request,
        CustomizationHelper $customizationHelper,
        SessionManagerInterface $coreSession,
        array $data = []
    ) {
        $this->_isScopePrivate = true;
        $this->request = $request;
        $this->_customizationHelper = $customizationHelper;
        $this->_coreSession = $coreSession;
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
        $getValuesMBaseSize = explode(",",$product->getResource()->getAttribute('m_hair_cut_styled_have')->getFrontend()->getValue($product));
        foreach ($getValuesMBaseSize as $attribute) {
            $returnResult[] = array (
                'attrName' => 'm_hair_cut_styled_have',
                'optionTitle' => $attribute,
                'value' => '',
                'multiple' => 0,
                'parentOptionName' => array(
                    'Yes, have hair cut-in and styled'
                ),
            );
        }
        $getValuesMBaseSize = explode(",",$product->getResource()->getAttribute('m_cut_style')->getFrontend()->getValue($product));
        foreach ($getValuesMBaseSize as $attribute) {
            $returnResult[] = array (
                'attrName' => 'm_cut_style',
                'optionTitle' => $attribute,
                'value' => '',
                'multiple' => 0,
                'parentOptionName' => array(
                    'Choose your hairstyles'
                ),
            );
        }
        $returnResult[] = array (
            'attrName' => 'm_base_size_section',
            'value' => '',
            'multiple' => 1,
            'multiOptions' => array (
                array(
                    'attrName' => 'Hair Cut Length',
                    'type' => 'range',
                    'optionTitle' => '1. Front',
                    'min' => '1',
                    'max' => '6',
                    'step' => '0.125',
                    'value' => '0.125',
                ),
                array(
                    'attrName' => 'Hair Cut Length',
                    'type' => 'range',
                    'optionTitle' => '2. Top',
                    'min' => '1',
                    'max' => '6',
                    'step' => '0.125',
                    'value' => '0.125',
                ),
                array(
                    'attrName' => 'Hair Cut Length',
                    'type' => 'range',
                    'optionTitle' => '3. Crown',
                    'min' => '1',
                    'max' => '6',
                    'step' => '0.125',
                    'value' => '0.125',
                ),
                array(
                    'attrName' => 'Hair Cut Length',
                    'type' => 'range',
                    'optionTitle' => '4. Back',
                    'min' => '1',
                    'max' => '6',
                    'step' => '0.125',
                    'value' => '0.125',
                ),
                array(
                    'attrName' => 'Hair Cut Length',
                    'type' => 'range',
                    'optionTitle' => '5,6. Temples',
                    'min' => '1',
                    'max' => '6',
                    'step' => '0.125',
                    'value' => '0.125',
                ),
                array(
                    'attrName' => 'Hair Cut Length',
                    'type' => 'range',
                    'optionTitle' => '7,8. Sides',
                    'min' => '1',
                    'max' => '6',
                    'step' => '0.125',
                    'value' => '0.125',
                )
            ),
            'parentOptionName' => array(
                'I want to order my length',
            ),
        );
        $returnResult[] = array (
            'attrName' => '',
            'optionTitle' => '',
            'value' => '',
            'multiple' => 0,
            'parentOptionName' => array(
                'Upload hairstyle images you want'
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
            if (isset($option['parentOptionName']) && in_array(trim($selectedPostOptionAttr), $option['parentOptionName']))
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
                    $hairCutImages = $this->getSessionHairCutImagesValue();
                    $newChildOption = array(
                        'inputType' => 'imageuploader',
                        'inputValue' => $hairCutImages
                    );
                    $savSession = array (
                        'attrName' => '',
                        'optionTitle' => $selectedPostOptionAttr,
                        'type' => 'multiple',
                        'current' => 1,
                        'multiple' => 1,
                        'priceRule' => '',
                        'parentOptionName' => $selectedPostCurrentParent,
                        'childOption' => $newChildOption
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
                    'No, I will have my hair cut-in and styled by my stylist',
                    'I\'ll send email to Lordhair',
                    'm_cut_style',
                    'm_cut_style_upload',
                    'm_hair_cut_styled_have_length'
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
                        if ($newKey == 'm_hair_cut_styled_have' && $isUnsetHairPerc) {
                            unset($value['options']['m_hair_cut_styled_have']);
                        }
                        if ($newKey == 'm_cut_style' && $isUnsetHairPerc) {
                            unset($value['options']['m_cut_style']);
                        }
                        if ($newKey == 'm_cut_style_upload' && $isUnsetHairPerc) {
                            unset($value['options']['m_cut_style_upload']);
                        }
                        if ($newKey == 'm_hair_cut_styled_have_length' && $isUnsetHairPerc) {
                            unset($value['options']['m_hair_cut_styled_have_length']);
                        }
                    }
                }
                $value['options'][$selectedPostCurrAttr] = $savSession;
                //save current option attribute session
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
                    if (isset($newValue['parentOptionName']) && $newValue['parentOptionName'] == self::MIAN_ATTR_NAME) {
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
                'attrName' => 'No&comma; I will have my hair cut-in and styled by my stylist',
                'attrImages' => array (
                    'mainImage' => 'ic_no_bleach_knots.png',
                    'thumbImage' => 'ic_no_bleach_knots.png',
                ),
            ),
            array (
                'attrName' => 'Yes&comma; have hair cut-in and styled',
                'attrImages' => array (
                    'mainImage' => 'img_hair_cut-in_style.jpg',
                    'thumbImage' => 'img_hair_cut-in_style.jpg',
                ),
            ),
            array (
                'attrName' => 'Yes&comma; have hair cut-in and styled(need extra 3 working days)',
                'attrImages' => array (
                    'mainImage' => 'img_hair_cut-in_style.jpg',
                    'thumbImage' => 'img_hair_cut-in_style.jpg',
                ),
            ),
            array (
                'attrName' => 'Choose your hairstyles',
                'attrImages' => array (
                    'mainImage' => 'ic_choose_your_hairstyle.png',
                    'thumbImage' => 'ic_choose_your_hairstyle.png',
                ),
            ),
            array (
                'attrName' => 'I want to order my length',
                'attrImages' => array (
                    'mainImage' => 'ic_dimension.png',
                    'thumbImage' => 'ic_dimension.png',
                ),
            ),
            array (
                'attrName' => 'I\'ll send email to Lordhair',
                'attrImages' => array (
                    'mainImage' => 'ic_send_email.png',
                    'thumbImage' => 'ic_send_email.png',
                ),
            ),
            array (
                'attrName' => 'Upload hairstyle images you want',
                'attrImages' => array (
                    'mainImage' => 'ic_upload_hairstyle_images.png',
                    'thumbImage' => 'ic_upload_hairstyle_images.png',
                ),
            ),
            array (
                'attrName' => '1. Front',
                'attrImages' => array (
                    'mainImage' => 'Front.png',
                    'thumbImage' => 'Front.png',
                ),
            ),
            array (
                'attrName' => '2. Top',
                'attrImages' => array (
                    'mainImage' => 'Top.png',
                    'thumbImage' => 'Top.png',
                ),
            ),
            array (
                'attrName' => '3. Crown',
                'attrImages' => array (
                    'mainImage' => 'Crown.png',
                    'thumbImage' => 'Crown.png',
                ),
            ),
            array (
                'attrName' => '4. Back',
                'attrImages' => array (
                    'mainImage' => 'Back.png',
                    'thumbImage' => 'Back.png',
                ),
            ),
            array (
                'attrName' => '5,6. Temples',
                'attrImages' => array (
                    'mainImage' => 'Temples.png',
                    'thumbImage' => 'Temples.png',
                ),
            ),
            array (
                'attrName' => '7,8. Sides',
                'attrImages' => array (
                    'mainImage' => 'Sides.png',
                    'thumbImage' => 'Sides.png',
                ),
            )
        );

        $attrName = trim($attrName);
        if( 1 == strpos('0'.$attrName, 'LD00')){
            $returnResult = array (
                'mainImage' => $attrName.'.png',
                'thumbImage' => $attrName.'.png',
            );            
        }else{
            $returnResult = array (
                'mainImage' => 'order_your_length.png',
                'thumbImage' => 'order_your_length.png',
            );

            foreach ($imagesArr as $singleImage) {
                if (strcmp(trim($singleImage['attrName']), $attrName) == 0) {
                    $returnResult = $singleImage['attrImages'];
                    break;
                }
            }
        }

        return $returnResult;
    }

    public function getSessionHairCutImagesValue(){
        $this->_coreSession->start();
        return $this->_coreSession->getHairCutImages();
    }
}