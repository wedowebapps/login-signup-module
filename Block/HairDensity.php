<?php
namespace Lordhair\Customizations\Block;
use Magento\Framework\View\Element\Template;
use Lordhair\Customizations\Helper\Data as CustomizationHelper;

class HairDensity extends Template
{
    const STEP_TITLE = 'Hair density';
    const MIAN_ATTR_NAME = 'm_hair_density';

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
                    'm_hair_density'
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
                $activeAttrOption = $value['optionTitle'];
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
        $specialPrice = $product->getPriceInfo()->getPrice('special_price')->getValue();
        $baseCurCode = $this->_customizationHelper->getBaseCurrencyCode();
        $currentCurCode = $this->_customizationHelper->getCurCurrencyCode();
        if ($currentCurCode != $baseCurCode) {
            $specialPrice = $product->getSpecialPrice();
        }

        if(array_key_exists($filterOptionTitle,$prices))
        {
            $optionPrice = $prices[$filterOptionTitle];

            if (strpos(trim($optionTitle), 'Medium 120') !== false) {
                $returnPrice = $this->_customizationHelper->getDensityPrice();
                $optionPrice = $specialPrice * 0.05;
                if ($returnPrice) {
                    $optionPrice = ($specialPrice + $returnPrice) * 0.05;
                }
            }
            if (strpos(str_replace('&comma;', ',', trim($optionTitle)), 'Medium heavy') !== false) {
                $returnPrice = $this->_customizationHelper->getDensityPrice();
                $optionPrice = $specialPrice * 0.15;
                if ($returnPrice) {
                    $optionPrice = ($specialPrice + $returnPrice) * 0.15;
                }
            }
            if (strpos(str_replace('&comma;', ',', trim($optionTitle)), 'Heavy') !== false) {
                $returnPrice = $this->_customizationHelper->getDensityPrice();
                $optionPrice = $specialPrice * 0.25;
                if ($returnPrice) {
                    $optionPrice = ($specialPrice + $returnPrice) * 0.25;
                }
            }
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
        $attributeName = self::MIAN_ATTR_NAME;
        $getPostOptionData = $getPostParams['optionData'];
        $getCurrentAttr = $getPostOptionData['currentAttr'];

        if ($getPostParams['isPrevClicked']) {

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

    public function getImagesByAttrName($attrName) {
        $imagesArr = array (
            array (
                'attrName' => 'Same as my last order',
                'attrImages' => array (
                    'mainImage' => 'ic_same_as_my_last_order.png',
                    'thumbImage' => 'ic_same_as_my_last_order.png',
                ),
            ),
            array (
                'attrName' => 'Same as the old unit I\'ll send in',
                'attrImages' => array (
                    'mainImage' => 'ic_old_unit.png',
                    'thumbImage' => 'ic_old_unit.png',
                ),
            ),
            array (
                'attrName' => 'Extra light 60%',
                'attrImages' => array (
                    'mainImage' => 'hair_den_ExtraLight_big.jpg',
                    'thumbImage' => 'hair_den_ExtraLight.jpg',
                ),
            ),
            array (
                'attrName' => 'Extra light',
                'attrImages' => array (
                    'mainImage' => 'hair_den_ExtraLight_big.jpg',
                    'thumbImage' => 'hair_den_ExtraLight.jpg',
                ),
            ),
            array (
                'attrName' => 'Light 80%',
                'attrImages' => array (
                    'mainImage' => 'hair_den_Light_big.jpg',
                    'thumbImage' => 'hair_den_Light.jpg',
                ),
            ),
            array (
                'attrName' => 'Light',
                'attrImages' => array (
                    'mainImage' => 'hair_den_Light_big.jpg',
                    'thumbImage' => 'hair_den_Light.jpg',
                ),
            ),
            array (
                'attrName' => 'Medium light 100%',
                'attrImages' => array (
                    'mainImage' => 'hair_den_MediumLight_big.jpg',
                    'thumbImage' => 'hair_den_MediumLight.jpg',
                ),
            ),
            array (
                'attrName' => 'Medium light',
                'attrImages' => array (
                    'mainImage' => 'hair_den_MediumLight_big.jpg',
                    'thumbImage' => 'hair_den_MediumLight.jpg',
                ),
            ),
            array (
                'attrName' => 'Medium 120%',
                'attrImages' => array (
                    'mainImage' => 'hair_den_Medium_big.jpg',
                    'thumbImage' => 'hair_den_Medium.jpg',
                ),
            ),
            array (
                'attrName' => 'Medium',
                'attrImages' => array (
                    'mainImage' => 'hair_den_Medium_big.jpg',
                    'thumbImage' => 'hair_den_Medium.jpg',
                ),
            ),
            array (
                'attrName' => 'Medium heavy 140%',
                'attrImages' => array (
                    'mainImage' => 'hair_den_MediumHeavy_big.jpg',
                    'thumbImage' => 'hair_den_MediumHeavy.jpg',
                ),
            ),
            array (
                'attrName' => 'Medium heavy',
                'attrImages' => array (
                    'mainImage' => 'hair_den_MediumHeavy_big.jpg',
                    'thumbImage' => 'hair_den_MediumHeavy.jpg',
                ),
            ),
            array (
                'attrName' => 'Heavy',
                'attrImages' => array (
                    'mainImage' => 'hair_den_Heavy_big.jpg',
                    'thumbImage' => 'hair_den_Heavy.jpg',
                ),
            )
        );
        $returnResult = array (
            'mainImage' => 'hair_den_MediumHeavy_big.jpg',
            'thumbImage' => 'hair_den_MediumHeavy.jpg',
        );
        foreach ($imagesArr as $singleImage) {
            if (strcmp(trim($attrName),trim($singleImage['attrName'])) == 0) {
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
}