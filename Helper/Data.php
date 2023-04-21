<?php
namespace Lordhair\Customizations\Helper;
use \Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Catalog\Model\Session as CatalogSession;
use Magento\Directory\Model\Currency as CurrencyModel;
use Magento\Cms\Model\BlockFactory;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\Pricing\Helper\Data as PriceFilter;

class Data extends AbstractHelper
{
    /**
     * @var StoreManagerInterface
     */
    private $storeConfig;

    /**
     * @var CurrencyFactory
     */
    private $currencyCode;

    /**
     * @var Magento\Catalog\Model\Session $catalogSession
     */
    protected $_catalogSession;

    /**
     * @var Magento\Directory\Model\Currency $currency
     */
    protected $_currency;

    /**
     * @var Magento\Cms\Model\BlockFactory $blockFactory
     */
    protected $_blockFactory;

    /**
     * @var Magento\Catalog\Model\ProductFactory $filterProvider
     */
    protected $_filterProvider;

    /**
     * @var Magento\Framework\Pricing\Helper\Data $priceHelper
     */
    protected $_priceHelper;

    /**
     * Currency constructor.
     *
     * @param StoreManagerInterface $storeConfig
     * @param CurrencyFactory $currencyFactory
     */
    public function __construct(
        StoreManagerInterface $storeConfig,
        CurrencyFactory $currencyFactory,
        CatalogSession $catalogSession,
        CurrencyModel $currency,
        BlockFactory $blockFactory,
        FilterProvider $filterProvider,
        PriceFilter $priceHelper
    ) {
        $this->storeConfig = $storeConfig;
        $this->currencyCode = $currencyFactory->create();
        $this->_catalogSession = $catalogSession;
        $this->_currency = $currency;
        $this->_blockFactory = $blockFactory;
        $this->_filterProvider = $filterProvider;
        $this->_priceHelper = $priceHelper;
    }

    public function convertToBaseCurrency($price)
    {
        return $this->_priceHelper->currency($price, false, false);
    }

    public function convertToBaseWithCurrency($price)
    {
        return $this->_priceHelper->currency($price, true, false);
    }

    public function convertPrice($price, $currencyCodeFrom, $currencyCodeTo)
    {
        $rate = $this->currencyCode->load($currencyCodeFrom)->getAnyRate($currencyCodeTo);
        $convertedPrice = $price * $rate;
        return $convertedPrice;
    }

    public function inArrayInsensitive($needle, $haystack)
    {
        return in_array( strtolower($needle), array_map('strtolower', $haystack) );
    }

    public function searchForTitle($title,$array,$step) {
        foreach ($array as $key => $val) {
            if (strtolower($val['title']) === strtolower($title)) {
                $val['title'] = $title;
                return $val;
            }
        }
        return null;
    }

    public function getTotalSteps($product) {

        $mainSteps = array(
            'Template & samples',
            'Base Design',
            'Base Material Color',
            'Front Contour',
            'Hair Length',
            'Curl & wave',
            'Curl wave',
            'Hair Direction',
            'Hair Color',
            'Highlight',
            'Grey Hair',
            'Bleach Knots',
            'Hair Type',
            'Hair Density',
            'Hair Cut',
            'Production Time',
            'Additional Instruction'
        );
        $groupId = 36;

        $i=1;
        $j=0;
        $totalSteps = array();
        $attributes = $product->getAttributes();

        foreach ($attributes as $attribute){
            if ($attribute->isInGroup($product->getAttributeSetId(), $groupId)){
                $_code = $attribute->getAttributeCode();
                $attributeLabel = $attribute->getStoreLabel();
                $getSelectedValues = $product->getResource()->getAttribute($_code)->getFrontend()->getValue($product);
                if ($getSelectedValues && $getSelectedValues != '') {
                    $getSelectedValues = explode(",",$getSelectedValues);
                    if ($getSelectedValues && is_array($getSelectedValues) && count($getSelectedValues) > 0 && $this->inArrayInsensitive($attributeLabel, $mainSteps)) {
                        if($j == 0)
                        {
                            $currerntValue = true;
                            $level = 0;
                        }
                        else
                        {
                            $currerntValue = false;
                        }
                        $totalSteps[] = array(
                            'step' => $j,
                            'title' => $attributeLabel,
                            'current' => $currerntValue,
                            'level' => $level,
                            'isCompleted' => 0,
                            'attribute_value' => $_code,
                            'options' => array(),
                            'price' => array()
                        );
                        $j++;
                    }
                }
            }
        }

        $totalSteps[] = array(
            'step' => $j,
            'title' => 'Additional Instruction',
            'current' => 0,
            'level' => 0,
            'isCompleted' => 0,
            'attribute_value' => 'm_additional_instruction',
            'options' => array(),
            'price' => array()
        );

        $array = array();
        $j=0;
        foreach ($mainSteps as $key => $title) {
            $getSearchArray = $this->searchForTitle($title,$totalSteps,$key);
            if ($getSearchArray) {
                switch ($getSearchArray['title']) {
                    case 'Template & samples':
                        $getSearchArray['title'] = 'Base Size';
                        break;
                    case 'Curl & wave':
                    case 'Curl wave':
                        $getSearchArray['title'] = 'Curl and Wave';
                        break;
                }
                $getSearchArray['step'] = $j;
                $array[] = $getSearchArray;
                $j++;
            }
        }
        return $array;
    }

    public function getOptionPriceArray($product) {

        $prices = array();
        $options = (array)$product->getOptions();
        foreach ($options as $option) {
            $optionValues = $option->getValues() ? $option->getValues() : [];
            if ($option->getTitle() == 'Upgrade price rule') {
                foreach ($optionValues as $optionValue) {
                    //$optionPrice = $this->convertToBaseCurrency($optionValue->getPrice());
                    //$prices[trim($optionValue->getTitle())] =  number_format($optionPrice, 2);
                    $prices[trim($optionValue->getTitle())] =  $optionValue->getPrice();
                }
            }
        }
        return $prices;
    }

    public function getOptionIdArray($product,$title) {
        $return = '';
        $options = (array)$product->getOptions();
        foreach ($options as $option) {
            $optionValues = $option->getValues() ? $option->getValues() : [];
            if ($option->getTitle() == 'Upgrade price rule') {
                foreach ($optionValues as $optionValue) {
                    if (strcmp(trim($optionValue->getTitle()), trim($title)) == 0) {
                        $return = $optionValue->getId();
                    }
                }
            }
        }
        return $return;
    }

    public function getSessionTotalSteps($product) {

        $_getSelectedOptions = $this->getSessionData('selectedOptions');

        $productId = $product->getID();

        if ($_getSelectedOptions && count($_getSelectedOptions) > 0 && $_getSelectedOptions['productId'] == $productId) {

            return $_getSelectedOptions['steps'];

        } else {

            $setSelectedOptionsArr = array (
                'steps'         => $this->getTotalSteps($product),
                'base_prices'   => array(),
                'current_price' => $product->getPrice(),
                'productId'     => $product->getID()
            );
            $this->setSessionData('selectedOptions', $setSelectedOptionsArr);
            $_getSelectedOptions = $setSelectedOptionsArr;

            return $_getSelectedOptions['steps'];
        }
    }

    public function getSessionData($key, $remove = false)
    {
        return $this->_catalogSession->getData($key, $remove);
    }

    public function setSessionData($key, $value)
    {
        return $this->_catalogSession->setData($key, $value);
    }

    public function getStore()
    {
        $return = $this->storeConfig->getStore();
        return $return;
    }

    public function getSiteMainUrl()
    {
        $return = $this->storeConfig->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK);
        return $return;
    }

    public function getBaseCurrencyCode()
    {
        return  $this->storeConfig->getStore()->getBaseCurrency()->getCode();
    }

    public function getCurCurrencyCode()
    {
        $currencyCode = $this->storeConfig->getStore()->getCurrentCurrencyCode();
        return $currencyCode;
    }

    public function getCurrentCurrencyCode()
    {
        $currencyCode = $this->storeConfig->getStore()->getCurrentCurrencyCode();
        $currencySymbol = $this->_currency->load($currencyCode)->getCurrencySymbol();
        return $currencySymbol ? $currencySymbol : $currencyCode;
    }

    public function getUpgradedPrice()
    {
        $returnPrice = array();
        $_getSelectedOptions = $this->getSessionData('selectedOptions');
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
        foreach ($_getSelectedOptions['steps'] as $singleStep) {
            foreach ($singleStep['options'] as $key=>$options){
                if (isset($options['priceRule']) && $options['priceRule'] != '' && in_array($key, $stepsForSelection)) {
                    $returnPrice[] = $options['priceRule'];
                }
            }
        }
        return array_sum($returnPrice);
    }

    public function getDensityPrice()
    {
        $returnPrice = array();
        $_getSelectedOptions = $this->getSessionData('selectedOptions');
        $stepsForSelection = array(
            'm_base_size',
            //'m_base_design',
            'm_template_samples',
            'm_base_size_section',
            'm_base_size_section_full_cap',
            'm_base_size_selection_topper',
            'm_hair_length',
            'm_hair_type',
            //'m_bleach_knots'
        );
        foreach ($_getSelectedOptions['steps'] as $singleStep) {
            foreach ($singleStep['options'] as $key=>$options){
                if (isset($options['priceRule']) && $options['priceRule'] != '' && in_array($key, $stepsForSelection)) {
                    $returnPrice[] = $options['priceRule'];
                }
            }
        }
        return array_sum($returnPrice);
    }

    public function getFrmtdPriceRule($optionPrice,$hairType=false)
    {
        if ($optionPrice) {
            if (strpos($optionPrice, '-') === 0) {
                $optionPrice = preg_replace('/-/', '', $optionPrice, 1);
                $optionPrice = '-'.$this->convertToBaseWithCurrency($optionPrice);
            } else {
                $optionPrice = '+'.$this->convertToBaseWithCurrency($optionPrice);
            }
        }
        return $optionPrice;
    }

    public function getDataPriceRule($price)
    {
        $price = str_replace(',', '', $price);
        $price = $this->convertToBaseCurrency($price);
        return $price;
    }

    public function helperStepSideDesc($stepCode)
    {
        $block = $this->_blockFactory->create();
        $storeId = $this->getStore()->getId();
        switch ($stepCode) {
            case 'm_base_design':
                $stepSubtitle = '<p><span class="open-btn">Click here</span> to know everything about base design and how it matters to your custom hair system:</p>';
                $block->setStoreId($storeId)->load('m_base_design_desc');
                break;
            case 'm_base_material_color':
                $stepSubtitle = '<p><span class="open-btn">Click here</span> to know everything about base material color and how it matters to your custom hair system:</p>';
                $block->setStoreId($storeId)->load('m_base_material_color_desc');
                break;
            case 'm_front_contour';
                $stepSubtitle = '<p><span class="open-btn">Click here</span> to know everything about front contour and how it matters to your custom hair system:</p>';
                $block->setStoreId($storeId)->load('m_front_contour_desc');
                break;
            case 'm_hair_length';
                $stepSubtitle = '<p><span class="open-btn">Click here</span> to know everything about hair length and how it matters to your custom hair system:</p>';
                $block->setStoreId($storeId)->load('m_hair_length_desc');
                break;
            case 'm_hair_density';
                $stepSubtitle = '<p><span class="open-btn">Click here</span> to know everything about hair density and how it matters to your custom hair system:</p>';
                $block->setStoreId($storeId)->load('m_hair_density_desc');
                break;
            case 'm_curl_wave';
            case 'm_curl_wave_type';
                $stepSubtitle = '<p><span class="open-btn">Click here</span> to know everything about curl & wave and how it matters to your custom hair system:</p>';
                $block->setStoreId($storeId)->load('m_curl_and_wave_desc');
                break;
            case 'm_hair_direction';
                $stepSubtitle = '<p><span class="open-btn">Click here</span> to know everything about hair direction and how it matters to your custom hair system:</p>';
                $block->setStoreId($storeId)->load('m_hair_direction_desc');
                break;
            case 'm_hair_color';
                $stepSubtitle = '<p><span class="open-btn">Click here</span> to know everything about hair color and how it matters to your custom hair system:</p>';
                $block->setStoreId($storeId)->load('m_hair_color_desc');
                break;
            case 'm_highlight';
                $stepSubtitle = '<p><span class="open-btn">Click here</span> to know everything about highlights and how it matters to your custom hair system:</p>';
                $block->setStoreId($storeId)->load('m_highlight_desc');
                break;
            case 'm_grey_hair';
                $stepSubtitle = '<p><span class="open-btn">Click here</span> to know everything about grey hair and how it matters to your custom hair system:</p>';
                $block->setStoreId($storeId)->load('m_grey_hair_desc');
                break;
            case 'm_bleach_knots';
                $stepSubtitle = '<p><span class="open-btn">Click here</span> to know everything about bleach knots and how it matters to your custom hair system:</p>';
                $block->setStoreId($storeId)->load('m_bleach_knots_desc');
                break;
            case 'm_hair_type';
                $stepSubtitle = '<p><span class="open-btn">Click here</span> to know everything about hair type and how it matters to your custom hair system:</p>';
                $block->setStoreId($storeId)->load('m_hair_type_desc');
                break;
            case 'm_cut_in_and_style';
                $stepSubtitle = '<p><span class="open-btn">Click here</span> to know everything about hair cut and how it matters to your custom hair system:</p>';
                $block->setStoreId($storeId)->load('m_cut_in_and_style_desc');
                break;
            case 'm_base_production_time';
                $stepSubtitle = '<p><span class="open-btn">Click here</span> The production cycles here are just estimated based on the automatic calculation of our internal system. The exact length of cycle is subject to real-time progress of your order.</p><p>You can also contact our customer service team to get a general idea of the exact date of completion.</p>';
                $block->setStoreId($storeId)->load('m_base_production_time_desc');
                break;
            case 'm_additional_instruction';
                $stepSubtitle = '<p>If there are any special instructions you have for this custom order, please leave them in the box. If not, please skip this step.</p>';
                $block->setStoreId($storeId)->load('m_base_production_time_desc');
                break;
            case 'm_base_size':
            case 'm_template_samples':
                $stepSubtitle = '<p><span class="open-btn">Click here</span> to know everything about base size and how it matters to your custom hair system:</p>';
                $block->setStoreId($storeId)->load('template_samples_desc');
                break;
            case 'perm_service';
            case 'perm_service_options';
                $stepSubtitle = '';
                break;
            default:
                $stepSubtitle = '';
        }
        $leftSidebarHtml = $this->_filterProvider->getBlockFilter()->setStoreId($storeId)->filter($block->getContent());
        $leftTitle = $this->_filterProvider->getBlockFilter()->setStoreId($storeId)->filter($block->getTitle());
        $returnArray = array(
            'stepSubtitle'      => $stepSubtitle,
            'leftTitle'         => $leftTitle,
            'leftSidebarHtml'   => $leftSidebarHtml
        );
        return $returnArray;
    }

    public function getLeftSideMainHtml($product,$currentStepCode)
    {
        $shortDesc = $this->helperStepSideDesc($currentStepCode);
        $imagewidth = $imageheight = 300;
        $upgradedPrice = array();
        $productImage = $this->getSiteMainUrl().'media/catalog/product' .$product->getImage();
        $_selectedOpts = $this->getSessionData('selectedOptions');
        $productPrice = $product->getPriceInfo()->getPrice('special_price')->getValue();
        $returnPrice = array();
        if ($_selectedOpts && isset($_selectedOpts['current_price'])) {
            foreach ($_selectedOpts['steps'] as $singleStep) {
                foreach ($singleStep['options'] as $key=>$options){
                    if (isset($options['priceRule']) && $options['priceRule'] != '') {
                        $convertedPrice = $this->convertToBaseCurrency($options['priceRule']);
                        $upgradedPrice[$options['parentOptionName']] = $convertedPrice;
                        $returnPrice[] = $convertedPrice;
                    }
                }
            }
        }
        if(array_sum($returnPrice)) {
            $productPrice = $productPrice + array_sum($returnPrice);
        }

        $html = '';
        $html = '<div class="mobile-price-details">
            <div class="product-intro">
                <img src="'.$productImage.'">
            </div>
            <div class="sm-product-details-wrp">
                <h5>SKU: '.$product->getSku().'</h5>
                <p>'.__('Total Price').': <span class="currencySymbol">'.$this->getCurrentCurrencyCode().'</span><span class="totalProdPrice">'.$productPrice.'</span></p>
            </div>
            <div class="close-me">×</div>
        </div>
        <div class="sidebar-menu-outer-content">
            <div class="sidebar-logo">
                <img src="'.$this->getSiteMainUrl().'media/customization/Lordhair-logo.png">
            </div>
            <div class="outer-quote">
                <img src="'.$this->getSiteMainUrl().'media/customization/quote.png">
            </div>
            <div class="outer-quote-text">
                <p><span class="stepShortDesc">'.$shortDesc['stepSubtitle'].'</span></p>
            </div>
            <div class="base-hover">
                <img src="'.$productImage.'">
            </div>
        </div>
        <div class="sidebar-menu">
            <div class="sidenav-inner-wrap">
                <div class="close-btn">
                    <div class="close-btn-wrp">×</div>
                </div>
                <h3><img alt="'.$product->getSku().'" src="'.$this->getSiteMainUrl().'media/customization/quote.png">'.__($shortDesc['leftTitle']).'</h3>
                <div class="stepFullDesc">'.$shortDesc['leftSidebarHtml'].'</div>
            </div>
        </div>';
        $return = array(
            'html' => $html,
            'finalPrice' => $productPrice,
            'upgradedPrice' => $upgradedPrice
        );
        return $return;
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

    public function getStepTtitleByAttributeValue($newKey, $defaultTitle) {
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
            case 'women_cut_style':
                $key = 'Cut style';
                break;
            default:
                $key = $defaultTitle;
        }

        return $key;
    }

    public function getFirstOptionByRequiredOptions($product = null, $resultPage=null) {
        if (!$product || !$resultPage) {
            return null;
        }
        $rendererBlock = ObjectManager::getInstance()->create(\Lordhair\CartEdit\Block\Cart\Item\Renderer::class, ['data' => []]);
        $requiredOptions = $rendererBlock->getRequiredOptions($product);
        $getSelectedSessionOptions = $this->getSessionData('selectedOptions');
        foreach ($getSelectedSessionOptions['steps'] as $step) {
            $optionValue = array();
            foreach ($step['options'] as $newKey => $newValue) {
                if ($newValue['parentOptionName'] == 'm_hair_color' && $newKey == 'm_color_code') {
                    $newKey = 'm_color_code_hair';
                }
                $key = $this->getStepTtitleByAttributeValue($newKey, $step['title']);
                if ($newValue['type'] == 'range') {
                    foreach($newValue['childOption'] as $childValue) {
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
                } if ($newValue['type'] == 'multiple') {
                    $optionValue[] = 'Hair Cut Additional Instruction:'.str_replace("'", "spchar39", $newValue['optionTitle']);
                    $optionValue[] = $newValue['childOption']['inputType'].':'.json_encode($newValue['childOption']['inputValue']);
                } else {
                    $ignoreAttr = array(
                        'm_hair_length',
                        'm_highlight_percentage',
                        'm_how_much_grey_hair_percentage',
                        'm_hair_cut_styled_have_length',
                        'm_hair_length_root',
                    );
                    if (!in_array($newKey, $ignoreAttr)) {
                        $optionValue[] = $key.':'.str_replace("'", "spchar39", $newValue['optionTitle']);
                    }
                }
            }
            $block = null;
            switch ($step['attribute_value']) {
                case 'm_base_material_color':
                    $block = $resultPage->getLayout()
                        ->createBlock('Lordhair\Customizations\Block\MaterialColor');
                    break;

                case 'm_base_design':
                    $block = $resultPage->getLayout()
                        ->createBlock('Lordhair\Customizations\Block\BaseDesign');
                    break;

                case 'm_front_contour':

                    $block = $resultPage->getLayout()
                        ->createBlock('Lordhair\Customizations\Block\FrontContour');
                    break;

                case 'm_hair_length':

                    $block = $resultPage->getLayout()
                        ->createBlock('Lordhair\Customizations\Block\HairLength');
                    break;

                case 'm_hair_density':

                    $block = $resultPage->getLayout()
                        ->createBlock('Lordhair\Customizations\Block\HairDensity');
                    break;

                case 'm_curl_wave':

                    $block = $resultPage->getLayout()
                        ->createBlock('Lordhair\Customizations\Block\CurlWave');
                    break;

                case 'm_curl_wave_type':
                    $block = $resultPage->getLayout()
                        ->createBlock('Lordhair\Customizations\Block\CurlWaveWomen');
                    break;

                case 'm_hair_direction':

                    $block = $resultPage->getLayout()
                        ->createBlock('Lordhair\Customizations\Block\Hairdirection');
                    break;

                case 'm_hair_color':

                    $block = $resultPage->getLayout()
                        ->createBlock('Lordhair\Customizations\Block\HairColor');
                    break;
                case 'm_highlight':

                    $block = $resultPage->getLayout()
                        ->createBlock('Lordhair\Customizations\Block\Highlight');
                    break;
                case 'm_grey_hair':

                    $block = $resultPage->getLayout()
                        ->createBlock('Lordhair\Customizations\Block\GreyHair');
                    break;
                case 'm_hair_type':

                    $block = $resultPage->getLayout()
                        ->createBlock('Lordhair\Customizations\Block\HairType');
                    break;
                case 'm_bleach_knots':
                    $block = $resultPage->getLayout()
                        ->createBlock('Lordhair\Customizations\Block\BleachKnots');
                    break;
                case 'm_cut_in_and_style':

                    $block = $resultPage->getLayout()
                        ->createBlock('Lordhair\Customizations\Block\HairCut');
                    break;
                case 'm_base_production_time':

                    $block = $resultPage->getLayout()
                        ->createBlock('Lordhair\Customizations\Block\ProductionTime');
                    break;
                case 'm_additional_instruction':

                    $block = $resultPage->getLayout()
                        ->createBlock('Lordhair\Customizations\Block\Additional');
                    break;
                default:
                    $block = $resultPage->getLayout()
                        ->createBlock('Lordhair\Customizations\Block\BaseSize');
            }
            $setCurrentStepSession = $block->_getStepInnerOption($product);
            $title = $this->getStepTtitleByAttributeValue($step['attribute_value'], $step['title']);
            if (empty($optionValue) && in_array(ucfirst(strtolower($title)), $requiredOptions)) {
                return $step;
            }
            unset($title);
            if ($setCurrentStepSession) {
                $isSelectedAll = true;
                foreach ($optionValue as $_option){
                    list($title, $val) = explode(':', $_option);
                    if (!in_array($title, $requiredOptions)) {
                        continue;
                    }
                    foreach ($setCurrentStepSession as $option) {
                        $newVal = str_replace("spchar39", "'", $val);
                        if (in_array(trim($newVal), $option['parentOptionName']) && !empty($option['optionTitle']) && empty($option['multiOptions'])  && count($optionValue) <= 1) {
                            $isSelectedAll = false;
                            break;
                        }elseif(in_array(trim($newVal), $option['parentOptionName']) && !empty($option['multiOptions'])  && count($optionValue) < 2) {
                            $isSelectedAll = false;
                            break;
                        }
                    }
                }
                if (!$isSelectedAll) {
                    return $step;
                }
            }
        }

        return null;
    }
}
