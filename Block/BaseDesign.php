<?php
namespace Lordhair\Customizations\Block;
use Magento\Framework\View\Element\Template;
use Lordhair\Customizations\Helper\Data as CustomizationHelper;

class BaseDesign extends Template
{

    const STEP_TITLE = 'Base Design';
    const MIAN_ATTR_NAME = 'm_base_design';

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
            if (strcmp(trim($step['title']), trim(self::STEP_TITLE)) == 0){
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

    public function getImagesByAttrName($attrName) {
        $imagesArr = array (
            array (
                'attrName' => 'I will send in an old unit for your reference',
                'attrImages' => array (
                    'mainImage' => 'ic_old_unit.png',
                    'thumbImage' => 'ic_old_unit.png',
                ),
            ),
            array (
                'attrName' => 'I have my own base design and will type in instructions',
                'attrImages' => array (
                    'mainImage' => 'ic_templates_and_samples.png',
                    'thumbImage' => 'ic_templates_and_samples.png',
                ),
            ),
            array (
                'attrName' => 'The same as my last order',
                'attrImages' => array (
                    'mainImage' => 'ic_same_as_my_last_order.png',
                    'thumbImage' => 'ic_same_as_my_last_order.png',
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
                'attrName' => 'L15 French lace with PU edge on sides & back',
                'attrImages' => array (
                    'mainImage' => 'l15-french-lace-with-pu-edge.jpg',
                    'thumbImage' => 'l15-french-lace-with-pu-edge.jpg',
                ),
            ),
            array (
                'attrName' => 'L16 Fine welded mono with PU edge on sides & back',
                'attrImages' => array (
                    'mainImage' => 'l16-fine-welded-mono-with-pu-edge.jpg',
                    'thumbImage' => 'l16-fine-welded-mono-with-pu-edge.jpg',
                ),
            ),
            array (
                'attrName' => 'S1 Thin skin all over',
                'attrImages' => array (
                    'mainImage' => 's1-thin-skin-all-over.jpg',
                    'thumbImage' => 's1-thin-skin-all-over.jpg',
                ),
            ),
            array (
                'attrName' => 'S12 Thin skin with mono lace front( zig zag connection)',
                'attrImages' => array (
                    'mainImage' => 's12-thin-skin-with-mono-lace-front.jpg',
                    'thumbImage' => 's12-thin-skin-with-mono-lace-front.jpg',
                ),
            ),
            array (
                'attrName' => 'S13 French lace with PU perimeter',
                'attrImages' => array (
                    'mainImage' => 's13-french-lace-with-pu-perimeter.jpg',
                    'thumbImage' => 's13-french-lace-with-pu-perimeter.jpg',
                ),
            ),
            array (
                'attrName' => 'S15 Thin skin with 1/4" French lace front',
                'attrImages' => array (
                    'mainImage' => 's15-thin-skin-with-french-lace-front.jpg',
                    'thumbImage' => 's15-thin-skin-with-french-lace-front.jpg',
                ),
            ),
            array (
                'attrName' => 'S16 Integration base with skin perimeter',
                'attrImages' => array (
                    'mainImage' => 's16-integration-base-with-skin-perimeter.jpg',
                    'thumbImage' => 's16-integration-base-with-skin-perimeter.jpg',
                ),
            ),
            array (
                'attrName' => 'S2 Fine welded mono lace all over',
                'attrImages' => array (
                    'mainImage' => 's2-fine-welded-mono-lace-all-over.jpg',
                    'thumbImage' => 's2-fine-welded-mono-lace-all-over.jpg',
                ),
            ),
            array (
                'attrName' => 'S3 Fine mono with PU perimeter',
                'attrImages' => array (
                    'mainImage' => 's3-fine-mono-with-pu-perimeter.jpg',
                    'thumbImage' => 's3-fine-mono-with-pu-perimeter.jpg',
                ),
            ),
            array (
                'attrName' => 'S4 Super fine mono with PU perimeter and 1/4" lace front',
                'attrImages' => array (
                    'mainImage' => 's4-super-fine-mono-with-pu-perimeter.jpg',
                    'thumbImage' => 's4-super-fine-mono-with-pu-perimeter.jpg',
                ),
            ),
            array (
                'attrName' => 'S4 Super fine mono with PU perimeter and 1/4 inch lace front',
                'attrImages' => array (
                    'mainImage' => 's4sfm.png',
                    'thumbImage' => 's4sfm.png',
                ),
            ),
            array (
                'attrName' => 'S7 French lace all over',
                'attrImages' => array (
                    'mainImage' => 's7-french-lace-all-over.jpg',
                    'thumbImage' => 's7-french-lace-all-over.jpg',
                ),
            ),
             array (
                'attrName' => 'Superskin-V 0.06mm super thin skin',
                'attrImages' => array (
                    'mainImage' => 'superskin-v-big.jpg',
                    'thumbImage' => 'superskin-v.jpg',
                ),
            ),
           
             array (
                'attrName' => 'Superskin 0.08mm thin skin',
                'attrImages' => array (
                    'mainImage' => 'superskin-big.jpg',
                    'thumbImage' => 'superskin.jpg',
                ),
            ),

           array (
                'attrName' => 'ThinSkin-VP 0.10mm thin skin',
                'attrImages' => array (
                    'mainImage' => 'thinskin-vp-big.jpg',
                    'thumbImage' => 'thinskin-vp.jpg',
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
                    $getPriceRule = '';
                    $savSession = array (
                        'attrName' => '',
                        'optionTitle' => '',
                        'type' => 'range',
                        'current' => 1,
                        'multiple' => 1,
                        'priceRule' => $getPriceRule,
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
                    'm_base_design'
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