<?php
/**
 * Copyright © Lordhair, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Lordhair\Customizations\Model\Reorder;

use Infortis\Infortis\Helper\Data as InfortisHelper;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Cart\CustomerCartResolver;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\GuestCart\GuestCartResolver;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Helper\Reorder as ReorderHelper;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection as ItemCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Pwa\CustomizationGraphQl\Helper\Data as CustomizationHelper;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Allows customer quickly to reorder previously added products and put them to the Cart
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Reorder extends \Magento\Sales\Model\Reorder\Reorder
{
    /**#@+
     * Error message codes
     */
    private const ERROR_PRODUCT_NOT_FOUND = 'PRODUCT_NOT_FOUND';
    private const ERROR_INSUFFICIENT_STOCK = 'INSUFFICIENT_STOCK';
    private const ERROR_NOT_SALABLE = 'NOT_SALABLE';
    private const ERROR_REORDER_NOT_AVAILABLE = 'REORDER_NOT_AVAILABLE';
    private const ERROR_UNDEFINED = 'UNDEFINED';
    /**#@-*/

    /**
     * List of error messages and codes.
     */
    private const MESSAGE_CODES = [
        'The required options you selected are not available' => self::ERROR_NOT_SALABLE,
        'Product that you are trying to add is not available' => self::ERROR_NOT_SALABLE,
        'This product is out of stock' => self::ERROR_NOT_SALABLE,
        'There are no source items' => self::ERROR_NOT_SALABLE,
        'The fewest you may purchase is' => self::ERROR_INSUFFICIENT_STOCK,
        'The most you may purchase is' => self::ERROR_INSUFFICIENT_STOCK,
        'The requested qty is not available' => self::ERROR_INSUFFICIENT_STOCK,
    ];

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var ReorderHelper
     */
    private $reorderHelper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var \Magento\Sales\Model\Reorder\Data\Error[]
     */
    private $errors = [];

    /**
     * @var CustomerCartResolver
     */
    private $customerCartProvider;

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var GuestCartResolver
     */
    private $guestCartResolver;

    protected $infortisHelper;

    protected $customizationHelper;

    protected $priceCurrency;

    /**
     * @param OrderFactory $orderFactory
     * @param CustomerCartResolver $customerCartProvider
     * @param GuestCartResolver $guestCartResolver
     * @param CartRepositoryInterface $cartRepository
     * @param ReorderHelper $reorderHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param ProductCollectionFactory $productCollectionFactory
     */
    public function __construct(
        OrderFactory $orderFactory,
        CustomerCartResolver $customerCartProvider,
        GuestCartResolver $guestCartResolver,
        CartRepositoryInterface $cartRepository,
        ReorderHelper $reorderHelper,
        \Psr\Log\LoggerInterface $logger,
        ProductCollectionFactory $productCollectionFactory,
        InfortisHelper $infortisHelper,
        CustomizationHelper $customizationHelper,
        PriceCurrencyInterface $priceCurrency
    ) {
        $this->orderFactory = $orderFactory;
        $this->cartRepository = $cartRepository;
        $this->reorderHelper = $reorderHelper;
        $this->logger = $logger;
        $this->customerCartProvider = $customerCartProvider;
        $this->guestCartResolver = $guestCartResolver;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->infortisHelper = $infortisHelper;
        $this->customizationHelper = $customizationHelper;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * Allows customer quickly to reorder previously added products and put them to the Cart
     *
     * @param string $orderNumber
     * @param string $storeId
     * @return \Magento\Sales\Model\Reorder\Data\ReorderOutput
     * @throws InputException Order is not found
     * @throws NoSuchEntityException The specified customer does not exist.
     * @throws \Magento\Framework\Exception\CouldNotSaveException Could not create customer Cart
     */
    public function execute(string $orderNumber, string $storeId): \Magento\Sales\Model\Reorder\Data\ReorderOutput
    {
        $order = $this->orderFactory->create()->loadByIncrementIdAndStoreId($orderNumber, $storeId);

        if (!$order->getId()) {
            throw new InputException(
                __('Cannot find order number "%1" in store "%2"', $orderNumber, $storeId)
            );
        }
        $customerId = (int)$order->getCustomerId();
        $this->errors = [];

        $cart = $customerId === 0
            ? $this->guestCartResolver->resolve()
            : $this->customerCartProvider->resolve($customerId);
        if (!$this->reorderHelper->isAllowed($order->getStore())) {
            $this->addError((string)__('Reorders are not allowed.'), self::ERROR_REORDER_NOT_AVAILABLE);
            return $this->prepareOutput($cart);
        }

        $this->addItemsToCart($cart, $order->getItemsCollection(), $storeId);

        try {
            $this->cartRepository->save($cart);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            // handle exception from \Magento\Quote\Model\QuoteRepository\SaveHandler::save
            $this->addError($e->getMessage());
        }

        $savedCart = $this->cartRepository->get($cart->getId());

        return $this->prepareOutput($savedCart);
    }

    /**
     * Add collections of order items to cart.
     *
     * @param Quote $cart
     * @param ItemCollection $orderItems
     * @param string $storeId
     * @return void
     */
    private function addItemsToCart(Quote $cart, ItemCollection $orderItems, string $storeId): void
    {
        $orderItemProductIds = [];
        /** @var \Magento\Sales\Model\Order\Item[] $orderItemsByProductId */
        $orderItemsByProductId = [];

        /** @var \Magento\Sales\Model\Order\Item $item */
        foreach ($orderItems as $item) {
            if ($item->getParentItem() === null) {
                $orderItemProductIds[] = $item->getProductId();
                $orderItemsByProductId[$item->getProductId()][$item->getId()] = $item;
            }
        }

        $products = $this->getOrderProducts($storeId, $orderItemProductIds);

        // compare founded products and throw an error if some product not exists
        $productsNotFound = array_diff($orderItemProductIds, array_keys($products));
        if (!empty($productsNotFound)) {
            foreach ($productsNotFound as $productId) {
                /** @var \Magento\Sales\Model\Order\Item $orderItemProductNotFound */
                $this->addError(
                    (string)__('Could not find a product with ID "%1"', $productId),
                    self::ERROR_PRODUCT_NOT_FOUND
                );
            }
        }

        foreach ($orderItemsByProductId as $productId => $orderItems) {
            if (!isset($products[$productId])) {
                continue;
            }
            $product = $products[$productId];
            foreach ($orderItems as $orderItem) {
                $this->addItemToCart($orderItem, $cart, clone $product);
            }
        }
    }

    /**
     * Get order products by store id and order item product ids.
     *
     * @param string $storeId
     * @param int[] $orderItemProductIds
     * @return Product[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getOrderProducts(string $storeId, array $orderItemProductIds): array
    {
        /** @var Collection $collection */
        $collection = $this->productCollectionFactory->create();
        $collection->setStore($storeId)
            ->addIdFilter($orderItemProductIds)
            ->addStoreFilter()
            ->addAttributeToSelect('*')
            ->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner')
            ->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner')
            ->addOptionsToResult();

        return $collection->getItems();
    }

    /**
     * Adds order item product to cart.
     *
     * @param OrderItemInterface $orderItem
     * @param Quote $cart
     * @param ProductInterface $product
     * @return void
     */
    private function addItemToCart(OrderItemInterface $orderItem, Quote $cart, ProductInterface $product): void
    {
        $info = $orderItem->getProductOptionByCode('info_buyRequest');
        // process old order
        $orderDetailOptionId = 0;
        $upgradePriceOptionId = 0;
        $upgradePriceValues = [];
        foreach ($product->getOptions() as $option) {
            if($option->getTitle() == 'Order details') {
                $orderDetailOptionId = $option->getOptionId();
            }else if ($option->getTitle() == 'Upgrade price rule') {
                $upgradePriceOptionId = $option->getOptionId();
                foreach ($option->getValues() as $value) {
                    $upgradePriceValues[$value->getOptionTypeId()] = trim($value->getTitle());
                }
            }
        }
        $isCustomProduct = $product->getResource()->getAttribute('is_custom_product')->getFrontend()->getValue($product);
        if (isset($info['options'])) {
            $upgradeSelectValueIds = [];
            $additionalInstructionVal = '';
            $baseSizeFullCap = 0;
            $baseSizeTopArea = 1;
            foreach ($info['options'] as $key => $option) {
                if ($option == '' || !in_array($key, [$orderDetailOptionId, $upgradePriceOptionId])) {
                    unset($info['options'][$key]);
                }
                if($this->infortisHelper->isJSON($option)) {
                    $newOption = json_decode($option, true);
                    if(isset($newOption['m_additional_instruction_text']) && isset($newOption['m_additional_instruction_text']['childOptions'])
                    && isset($newOption['m_additional_instruction_text']['childOptions'][0]['optionTitle']) &&
                        $newOption['m_additional_instruction_text']['childOptions'][0]['optionTitle'] &&
                        strpos($newOption['m_additional_instruction_text']['childOptions'][0]['optionTitle'], 'Additional Instruction:') !== false) {
                        $option = $newOption['m_additional_instruction_text']['childOptions'][0]['optionTitle'];
                    }
                }
                if(is_string($option) && strpos($option, ';') !== false && !$this->infortisHelper->isJSON($option)) {
                    $newOption = $option;
                    $newOption = str_replace('Yes,have hair cut-in and styled', 'Yes, have hair cut-in and styled', $newOption);
                    $newOption = str_replace('1.Front', '1. Front', $newOption);
                    $newOption = str_replace('2.Top', '2. Top', $newOption);
                    $newOption = str_replace('3.Crown', '3. Crown', $newOption);
                    $newOption = str_replace('4.Back', '4. Back', $newOption);
                    $newOption = str_replace('5,6.Temples', '5,6. Temples', $newOption);
                    $newOption = str_replace('7,8.Sides', '7,8. Sides', $newOption);
                    $newOption = str_replace('I′ll fill the dimension in below Base Size section', "I'll fill the dimension in below Base Size section", $newOption);
                    $newOption = str_replace('Base Size:;', '', $newOption);
                    $newOption = str_replace('Average service 8-12 weeks', 'Regular service 8-12 weeks', $newOption);
                    $newOption = str_replace('Average service 7-8 weeks', 'Rush service 6-8 weeks', $newOption);
                    $newOption = str_replace('Rush service 6-10 weeks', 'Rush service 6-8 weeks', $newOption);
                    $newOption = str_replace('Average service 10-14 weeks', 'Regular service 8-12 weeks', $newOption);
                    $newOption = str_replace('Average service At least 12 weeks', 'Regular service 8-12 weeks', $newOption);
                    $newOption = str_replace('Regular service 9-10 weeks', 'Regular service 8-12 weeks', $newOption);
                    $newOption = str_replace('Rush service 7-11 weeks', 'Regular service 8-12 weeks', $newOption);
                    $newOption  = str_replace('&quot;', '"', $newOption);
                    $newOption  = str_replace('&#39;', "'", $newOption);
                    $newOption  = str_replace('&amp;', '&', $newOption);
                    $newOption  = str_replace('&lt;', '<', $newOption);
                    $newOption  = str_replace('&gt;', '>', $newOption);
                    $newOption  = str_replace(';;', ';', $newOption);
                    $newOption  = str_replace(';;', ';', $newOption);
                    $newOption  = str_replace("'", "\'", $newOption);
                    $newOption = preg_replace('/(\'|&#0*39;)/', 'spchar39', $newOption);
                    $newOption = str_replace('&comma;', ',', $newOption);
                    foreach (explode(';', $newOption) as $_op) {
                        if(!$_op || strpos($_op, ':') === false) {
                            continue;
                        }

                        list($_opt, $_opv) = explode(':', $_op);
                        if(in_array($_opt, ['Cut-in & Style', 'Hair Cut']) && strpos($_opv, 'Yes') !== false &&
                            strpos($newOption, 'Cut Style My Length:') === false &&
                            strpos($newOption, 'Cut style:') !== false) {
                            $newOption = str_replace($_op.';', $_op.';'.'Cut Style My Length:Choose your hairstyles;', $newOption);
                            $cutStyleUpgradePriceId = array_search('Choose your hairstyles', $upgradePriceValues);
                            $cutStyleUpgradePriceId && $upgradeSelectValueIds[] = $cutStyleUpgradePriceId;
                        }
                        if (in_array($_opt, ['Scallop front'])) {
                            $additionalInstructionVal .= $_opt . '=' . $_opv . ' | ';
                            $newOption = str_replace($_op.';', '', $newOption);
                        }
                        if ($_opt == 'Additional instruction') {
                            $additionalInstructionVal = $additionalInstructionVal && $_opv ? $_opv . "\n" . $additionalInstructionVal : $_opv . $additionalInstructionVal;
                            $newOption = str_replace($_op.';', '', $newOption);
                            $newOption = str_replace($_op, '', $newOption);
                        }
                        if((strtolower($_opt) == 'hair length' || strtolower($_opt) == 'pefilled hair length') && $isCustomProduct == 'Yes') {
                            preg_match_all('!\d+!', trim($_opv), $matches);
                            $value  = 'Longest at '.$matches[0][0].'"';
                            if(($hairLengthUpgradePriceValueId = array_search($value, $upgradePriceValues)) !== false) {
                                $upgradeSelectValueIds[] = $hairLengthUpgradePriceValueId;
                            }
                        }
                        if($_opv && ($upgradePriceValueId = array_search(trim($_opv), $upgradePriceValues)) !== false) {
                            $upgradeSelectValueIds[] = $upgradePriceValueId;
                        }else if(in_array($_opt, ['Width', 'Length'])) {
                            $baseSizeTopArea *= floatval(str_replace('Inch', '', $_opv));
                        }else if(in_array($_opt, ['Circumference', 'Front to nape', 'Ear to ear across forehead',
                            'Temple to temple', 'Ear to ear over top', 'Temple to temple round back', 'Nape of neck'])) {
                            $baseSizeFullCap = 1;
                        }
                    }
                    unset($info['options'][$key]);
                    if ($isCustomProduct == 'Yes') {
                        if(strpos($newOption, 'Hair type:') === false) {
                            $newOption .= 'Hair type:;';
                        }
                        if(strpos($newOption, 'Hair density:') === false) {
                            $newOption .= 'Hair density:;';
                        }
                    }
                    $info['options'][$orderDetailOptionId] = $newOption . 'Additional Instruction:'.rtrim($additionalInstructionVal, ' |');
                }
            }
            $baseSizeUpgradeId = [];
            if($baseSizeFullCap == 1) {
                $baseSizeUpgradeId[] = array_search('Full cap (size > 10"x10", or area > 100 square inches)', $upgradePriceValues);
            }else if ($baseSizeTopArea != 1) {
                $upgradePriceLabel = '';
                switch (true) {
                    case $baseSizeTopArea < 70:
                        break;
                    case $baseSizeTopArea > 70 && $baseSizeTopArea <= 80:
                        $upgradePriceLabel = 'Regular (7"x10" < size ≤ 8"x10", or 70 square inches < area ≤ 80 square inches)';
                        break;
                    case $baseSizeTopArea > 80 && $baseSizeTopArea <= 100:
                        $upgradePriceLabel = 'Oversize (8"x10" < size ≤ 10"x10", or 80 square inches < area ≤ 100 square inches)';
                        break;
                    case $baseSizeTopArea > 100:
                        $upgradePriceLabel = 'Full cap (size > 10"x10", or area > 100 square inches)';
                        break;
                }
                if($upgradePriceLabel) {
                    $baseSizeUpgradeId[] = array_search($upgradePriceLabel, $upgradePriceValues);
                }
            }
            if($upgradePriceOptionId && ($upgradeSelectValueIds || $baseSizeUpgradeId)) {
                $info['options'][$upgradePriceOptionId] = $baseSizeUpgradeId ? array_merge($upgradeSelectValueIds, $baseSizeUpgradeId) : $upgradeSelectValueIds;
                $info['options'][$upgradePriceOptionId] = array_unique($info['options'][$upgradePriceOptionId]);
            }
        }


        $info = new \Magento\Framework\DataObject($info);
        $info->setQty($orderItem->getQtyOrdered());

        $addProductResult = null;
        try {
            $addProductResult = $cart->addProduct($product, $info);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->addError($this->getCartItemErrorMessage($orderItem, $product, $e->getMessage()));
        } catch (\Throwable $e) {
            $this->logger->critical($e);
            $this->addError($this->getCartItemErrorMessage($orderItem, $product), self::ERROR_UNDEFINED);
        }

        // error happens in case the result is string
        if (is_string($addProductResult)) {
            $errors = array_unique(explode("\n", $addProductResult));
            foreach ($errors as $error) {
                $this->addError($this->getCartItemErrorMessage($orderItem, $product, $error));
            }
        }
    }


    protected function getOptionArr($optionsStr, $product)
    {
        $attributes = $product->getAttributes();
        $isCustom = $product->getAttributeText('is_custom_product');
        $newOptionArr = [];
        $optionArr = explode(';', $optionsStr);
        $upgradePriceOptions = [];
        foreach ($product->getOptions() as $option) {
            if ($option->getTitle() == 'Upgrade price rule') {
                foreach ($option->getValues() as $value) {
                    $upgradePriceOptions[trim($value->getTitle())] = [
                        'id' => $value->getOptionTypeId(),
                        'price' => $this->priceCurrency->convertAndRound($value->getPrice())
                    ];
                }
            }
        }
        $this->customizationHelper->setCurrentProduct($product);
        if(strtolower($isCustom->getText()) == 'yes') {
            $attributeCodes = $this->customizationHelper->getAllCustomAttr();
            foreach($attributes as $attribute) {
                if(!in_array($attribute->getAttributeCode(), $attributeCodes)) {
                    continue;
                }
                $attributeLabel = $attribute->getStoreLabel();
                $newOptionArr = array_merge($newOptionArr,
                    $this->getCustomChildOptions($attribute->getId(), $attribute->getAttributeCode(), $attributeLabel, $optionArr, $product, $upgradePriceOptions)
                );
            }

            $additionalInstruction = [
                'attrId' => 1811,
                'attribute_value' => 'm_additional_instruction_text',
                'optionTitle' => 'Please type in your additional instruction.',
                'priceRule' => 0,
                'priceRuleId' => 0,
                'childOptions' => [
                    [
                        "attrId" => rand(100000, 999999),
                        'attribute_value' => 'm_additional_instruction',
                        'optionTitle' => $optionsStr,
                        'priceRule' =>  0,
                        'priceRuleId' =>  0,
                        'range' => null,
                        'rangeUnit' => null,
                    ]
                ]
            ];
            $newOptionArr['m_additional_instruction_text'] = $additionalInstruction;
        }

        return array_filter($newOptionArr);
    }

    protected function getOldColorCode($title)
    {
        $_title = str_replace('-', ' ', $title);
        $_titleArr = explode(' ', $_title);

        return $_titleArr[1] ?? '';
    }

    protected function getCustomChildOptions($attributeId, $attributeCode, $attributeLabel, $optionArr, $product, $upgradePriceOptions)
    {
        $result = [];
        $baseSizeTopperArea = 1;
        $attrIds = [
            'm_base_design' => 2,
            'm_base_material_color' => 3,
            'm_front_contour' => 4,
            'm_hair_length' => 5,
            'm_hair_direction' => 7,
            'm_bleach_knots' => 12,
            'm_hair_type' => 13,
            'm_hair_density' => 14,
            'm_base_production_time' => 16

        ];
        $isRootColor = false;
        foreach($optionArr as $tmp) {
            if(strpos($tmp, 'Root color') !== false) {
                $isRootColor = true;
                break;
            }
        }
        foreach($optionArr as $option) {
            if(!$option || strpos($option, ':') === false) {
                continue;
            }
            list($title, $value) = explode(':', $option);
            if(!$title) {
                continue;
            }
            switch($attributeCode) {
                case 'm_base_size_section':
                    if(in_array($title, [
                        'Template & samples',
                        'Base size',
                        'Base size selection',
                        'Width',
                        'Length',
                        'Circumference',
                        'Front to nape',
                        'Ear to ear across forehead',
                        'Temple to temple',
                        'Ear to ear over top',
                        'Temple to temple round back',
                        'Nape of neck'
                    ])) {
                        if(!isset($result[$attributeCode])) {
                            $result[$attributeCode] = [
                                'attrId' => 1,
                                'attribute_value' => $attributeCode,
                                'optionTitle' => '',
                                'priceRule' => 0,
                                'priceRuleId' => 0,
                                'childOptions' => []
                            ];
                        }
                        $fullCapAttIds = [
                            'Circumference' => 131,
                            'Front to nape' => 132,
                            'Ear to ear across forehead' => 133,
                            'Temple to temple' => 134,
                            'Ear to ear over top' => 135,
                            'Temple to temple round back' => 136,
                            'Nape of neck' => 137
                        ];
                        $isAttributeExist = $product->getResource()->getAttribute($attributeCode);

                        if(in_array($title, [
                            'Width',
                            'Length',
                        ])) {
                            $optionId = $isAttributeExist->getSource()->getOptionId('Topper');
                            $result[$attributeCode]['optionTitle'] =  'Topper';
                            $result[$attributeCode]['attrId'] =  $optionId;
                            $result[$attributeCode]['childOptions'][] = [
                                'attrId' => rand(1500,2000),
                                'attribute_value' => 'm_base_size_topper',
                                'optionTitle' => $title,
                                'priceRule' => 0,
                                'priceRuleId' => 0,
                                'range' =>  str_replace('inch', '', strtolower($value)),
                                'rangeUnit' => 'inch'
                            ];
                            $baseSizeTopperArea *= floatval(str_replace('inch', '', strtolower($value)));
                        }else if(in_array($title, [
                            'Circumference',
                            'Front to nape',
                            'Ear to ear across forehead',
                            'Temple to temple',
                            'Ear to ear over top',
                            'Temple to temple round back',
                            'Nape of neck'
                        ])){
                            $result[$attributeCode]['optionTitle'] =  'Full Cap';
                            $optionId = $isAttributeExist->getSource()->getOptionId('Full Cap');
                            $result[$attributeCode]['attrId'] =  $optionId;
                            $result[$attributeCode]['priceRule'] = isset($upgradePriceOptions[CustomizationHelper::Prices_Base_Size[2]]) ?
                                $upgradePriceOptions[CustomizationHelper::Prices_Base_Size[2]]['price'] : 0;
                            $result[$attributeCode]['priceRuleId'] = isset($upgradePriceOptions[CustomizationHelper::Prices_Base_Size[2]]) ?
                                $upgradePriceOptions[CustomizationHelper::Prices_Base_Size[2]]['id'] : 0;
                            $result[$attributeCode]['childOptions'][] = [
                                'attrId' => $fullCapAttIds[$title],
                                'attribute_value' => 'm_base_size_fullcap',
                                'optionTitle' => $title,
                                'priceRule' => 0,
                                'priceRuleId' => 0,
                                'range' =>  str_replace('inch', '', strtolower($value)),
                                'rangeUnit' => 'inch'
                            ];
                        }
                    }
                    break;

                case 'm_curl_wave_selection':
                    if(in_array($title, [
                        'Curl & wave',
                        'Curl and Wave',
                        'Curl wave'
                    ])) {
                        if(!isset($result[$attributeCode])) {
                            $result[$attributeCode] = [
                                'attrId' => 61,
                                'attribute_value' => $attributeCode,
                                'optionTitle' => '',
                                'priceRule' => 0,
                                'priceRuleId' => 0,
                                'childOptions' => []
                            ];
                        }
                        $stepOption = $this->customizationHelper->getSubCurlWaveOption(61, 'm_curl_wave');
                        if($stepOption && $stepOption['childOption']) {
                            foreach($stepOption['childOption'] as $op) {
                                if($op['childOption']) {
                                    foreach($op['childOption'] as $_op) {
                                        if($_op['optionTitle'] == $value) {
                                            $result[$attributeCode]['optionTitle'] = $op['optionTitle'];
                                            $op['optionTitle'] == 'Women' && $result[$attributeCode]['attrId'] = 62;
                                            $result[$attributeCode]['childOptions'][] = [
                                                'attrId' => $_op['attrId'],
                                                'attribute_value' => $op['attribute_value'],
                                                'optionTitle' => $value,
                                                'priceRule' => isset($upgradePriceOptions[trim($value)]) ?
                                                    $upgradePriceOptions[trim($value)]['price'] : 0,
                                                'priceRuleId' => isset($upgradePriceOptions[trim($value)]) ?
                                                    $upgradePriceOptions[trim($value)]['id'] : 0,
                                                'range' => null,
                                                'rangeUnit' => null,
                                            ];

                                            break 2;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    break;

                case 'm_hair_color_selection':
                    if(in_array($title, [
                        'Hair color',
                        'Hair color code'
                    ])) {
                        if(!isset($result[$attributeCode])) {
                            $result[$attributeCode] = [
                                'attrId' => 0,
                                'attribute_value' => $attributeCode,
                                'optionTitle' => '',
                                'priceRule' => 0,
                                'priceRuleId' => 0,
                                'childOptions' => []
                            ];
                        }
                        if($title == 'Hair color code') {
                            $stepOption = $this->customizationHelper->getSubHairColorOption(81,$attributeCode);
                            if($stepOption && $stepOption['childOption']) {
                                foreach($stepOption['childOption'] as $op) {
                                    if($op['childOption']) {
                                        foreach($op['childOption'] as $_op) {
                                            $_title = str_replace('-', ' ', $_op['optionTitle']);
                                            $_titleArr = explode(' ', $_title);
                                            if(isset($_titleArr[1]) && $_titleArr[1] == $value) {
                                                $result[$attributeCode]['optionTitle'] = $op['optionTitle'];
                                                $op['optionTitle'] == 'Women' && $result[$attributeCode]['attrId'] = 82;
                                                $result[$attributeCode]['childOptions'][] = [
                                                    'attrId' => $_op['attrId'],
                                                    'attribute_value' => $_op['attribute_value'],
                                                    'optionTitle' => $_op['optionTitle'],
                                                    'priceRule' => isset($upgradePriceOptions[trim($value)]) ?
                                                        $upgradePriceOptions[trim($value)]['price'] : 0,
                                                    'priceRuleId' => isset($upgradePriceOptions[trim($value)]) ?
                                                        $upgradePriceOptions[trim($value)]['id'] : 0,
                                                    'range' => null,
                                                    'rangeUnit' => null,
                                                ];

                                                break 2;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    break;

                case 'm_highlights_type_new':
                    if(in_array($title, [
                        'Highlight',
                        'Highlight Color',
                        'Highlight Percentage',
                        'Highlight Distribution',
                        'Highlight Percentage Front',
                        'Highlight Percentage Top',
                        'Highlight Percentage Crown',
                        'Highlight Percentage Back',
                        'Highlight Percentage Temples',
                        'Highlight Percentage Sides',
                        'Highlight Selection',
                        'Highlight Instructions',
                    ]) && !$isRootColor) {
                        if(!isset($result[$attributeCode])) {
                            $result[$attributeCode] = [
                                'attrId' => 0,
                                'attribute_value' => $attributeCode,
                                'optionTitle' => '',
                                'priceRule' => 0,
                                'priceRuleId' => 0,
                                'childOptions' => []
                            ];
                        }
                        if($title == 'Highlight Selection') {
                            $isAttributeExist = $product->getResource()->getAttribute($attributeCode);
                            $attrId = $isAttributeExist->getSource()->getOptionId(trim($value));
                            $result[$attributeCode]['attrId'] = $attrId;
                            $result[$attributeCode]['optionTitle'] = trim($value);
                            $result[$attributeCode]['priceRule'] = isset($upgradePriceOptions[trim($value)]) ?
                                $upgradePriceOptions[trim($value)]['price'] : 0;
                            $result[$attributeCode]['priceRuleId'] = isset($upgradePriceOptions[trim($value)]) ?
                                $upgradePriceOptions[trim($value)]['id'] : 0;
                        }else if($title == 'Highlight Color') {
                            $this->customizationHelper->setAttrOptionValues('m_color_code');
                            $menColorOptions = $this->customizationHelper->getCommonListItemSecnd(92, 'm_color_code');
                            if($menColorOptions && isset($menColorOptions['childOption'])) {
                                foreach($menColorOptions['childOption'] as $menColorOption) {
                                    if(isset($menColorOption['childOption']) && $menColorOption['childOption']) {
                                        foreach($menColorOption['childOption'] as $_mop) {
                                            if($this->getOldColorCode($_mop['optionTitle']) == $value) {
                                                $result[$attributeCode]['childOptions'][] = [
                                                    'attrId' => $_mop['attrId'],
                                                    'attribute_value' => $menColorOption['attribute_value'],
                                                    'optionTitle' => $_mop['optionTitle'],
                                                    'priceRule' => 0,
                                                    'priceRuleId' =>  0,
                                                    'range' => null,
                                                    'rangeUnit' => null,
                                                ];
                                            }
                                        }
                                    }
                                }
                            }
                            $this->customizationHelper->setAttrOptionValues('m_color_code_for_women');
                            $womenColorOptions = $this->customizationHelper->getCommonListItemSecnd(92, 'm_color_code_for_women');
                            if($womenColorOptions && isset($womenColorOptions['childOption'])) {
                                foreach($womenColorOptions['childOption'] as $womenColorOption) {
                                    if(isset($womenColorOption['childOption']) && $womenColorOption['childOption']) {
                                        foreach($womenColorOption['childOption'] as $_wmop) {
                                            if($this->getOldColorCode($_wmop['optionTitle']) == $value) {
                                                $result[$attributeCode]['childOptions'][] = [
                                                    'attrId' => $_wmop['attrId'],
                                                    'attribute_value' => $womenColorOption['attribute_value'],
                                                    'optionTitle' => $_wmop['optionTitle'],
                                                    'priceRule' => 0,
                                                    'priceRuleId' =>  0,
                                                    'range' => null,
                                                    'rangeUnit' => null,
                                                ];
                                            }
                                        }
                                    }
                                }
                            }


                        }else if(in_array($title, [
                            'Highlight Percentage Front',
                            'Highlight Percentage Top',
                            'Highlight Percentage Crown',
                            'Highlight Percentage Back',
                            'Highlight Percentage Temples',
                            'Highlight Percentage Sides',
                            'Highlight Percentage',
                        ])) {
          ;                 $map = [
                                'Highlight Percentage Front' => [
                                    'id' => 90021,
                                    'title' => 'Front',
                                ],
                                'Highlight Percentage Top' => [
                                    'id' => 90022,
                                    'title' => 'Top',
                                ],
                                'Highlight Percentage Crown' => [
                                    'id' => 90023,
                                    'title' => 'Crown',
                                ],
                                'Highlight Percentage Back' => [
                                    'id' => 90024,
                                    'title' => 'Back',
                                ],
                                'Highlight Percentage Temples' => [
                                    'id' => 90025,
                                    'title' => 'Temples',
                                ],
                                'Highlight Percentage Sides' => [
                                    'id' => 90026,
                                    'title' => 'Sides',
                                ],
                                'Highlight Percentage' => [
                                    'id' => 93,
                                    'title' => 'Proportion',
                                ]
                            ];
                            $result[$attributeCode]['childOptions'][] = [
                                'attrId' => $map[$title]['id'],
                                'attribute_value' => $result[$attributeCode]['optionTitle'] == 'Spot/Dot' ? 'highlight_spotdot' : 'highlight_evenly',
                                'optionTitle' => $map[$title]['title'],
                                'priceRule' => 0,
                                'priceRuleId' =>  0,
                                'range' => str_replace('%', '', $value),
                                'rangeUnit' => 'percentage',
                            ];
                        }
                    }
                    break;

                case 'root_color_selection':
                    if(in_array($title, [
                            'Highlight',
                            'Highlight Color',
                            'Highlight Selection',
                            'Highlight Hair Length',
                        ]) && $isRootColor) {
                        if(!isset($result[$attributeCode])) {
                            $result[$attributeCode] = [
                                'attrId' => 0,
                                'attribute_value' => $attributeCode,
                                'optionTitle' => '',
                                'priceRule' => 0,
                                'priceRuleId' => 0,
                                'childOptions' => []
                            ];
                        }

                        if($title == 'Highlight Selection') {
                            $isAttributeExist = $product->getResource()->getAttribute($attributeCode);
                            $optionId = $isAttributeExist->getSource()->getOptionId(trim($value));
                            $result[$attributeCode]['attrId'] = $optionId;
                            $result[$attributeCode]['optionTitle'] = $value;
                            $result[$attributeCode]['priceRule'] = isset($upgradePriceOptions[trim($value)]) ?
                                $upgradePriceOptions[trim($value)]['price'] : 0;
                            $result[$attributeCode]['priceRuleId'] = isset($upgradePriceOptions[trim($value)]) ?
                                $upgradePriceOptions[trim($value)]['id'] : 0;
                        }else if($title == 'Highlight Color' && isset($result[$attributeCode]) && $result[$attributeCode]['optionTitle']) {
                            $this->customizationHelper->setAttrOptionValues('m_color_code');
                            $menColorOptions = $this->customizationHelper->getCommonListItemSecnd(92, 'm_color_code', 'root_color_code_men');
                            if($menColorOptions && isset($menColorOptions['childOption'])) {
                                foreach($menColorOptions['childOption'] as $menColorOption) {
                                    if(isset($menColorOption['childOption']) && $menColorOption['childOption']) {
                                        foreach($menColorOption['childOption'] as $_mop) {
                                            if($this->getOldColorCode($_mop['optionTitle']) == $value) {
                                                $result[$attributeCode]['childOptions'][] = [
                                                    'attrId' => $_mop['attrId'],
                                                    'attribute_value' => $menColorOption['attribute_value'],
                                                    'optionTitle' => $_mop['optionTitle'],
                                                    'priceRule' => 0,
                                                    'priceRuleId' =>  0,
                                                    'range' => null,
                                                    'rangeUnit' => null,
                                                ];
                                            }
                                        }
                                    }
                                }
                            }
                            $this->customizationHelper->setAttrOptionValues('m_color_code_for_women');
                            $womenColorOptions = $this->customizationHelper->getCommonListItemSecnd(92, 'm_color_code_for_women', 'root_color_code_women');
                            if($womenColorOptions && isset($womenColorOptions['childOption'])) {
                                foreach($womenColorOptions['childOption'] as $womenColorOption) {
                                    if(isset($womenColorOption['childOption']) && $womenColorOption['childOption']) {
                                        foreach($womenColorOption['childOption'] as $_wmop) {
                                            if($this->getOldColorCode($_wmop['optionTitle']) == $value) {
                                                $result[$attributeCode]['childOptions'][] = [
                                                    'attrId' => $_wmop['attrId'],
                                                    'attribute_value' => $womenColorOption['attribute_value'],
                                                    'optionTitle' => $_wmop['optionTitle'],
                                                    'priceRule' => 0,
                                                    'priceRuleId' =>  0,
                                                    'range' => null,
                                                    'rangeUnit' => null,
                                                ];
                                            }
                                        }
                                    }
                                }
                            }
                        }else if($title == 'Highlight Hair Length' && isset($result[$attributeCode]) && $result[$attributeCode]) {
                            $this->helper->setAttrOptionValues('root_color_hair_length');
                            $stepOption = $this->helper->getCommonHairLengthOption(102,'root_color_hair_length',false);
                            if($stepOption && isset($stepOption['childOption']) && $stepOption['childOption']) {
                                foreach($stepOption['childOption'] as $_op) {
                                    preg_match_all('!\d+!', $_op['optionTitle'], $mop);
                                    preg_match_all('!\d+!', $value, $mvalue);
                                    if(isset($mop[0]) && isset($mop[0][0]) && isset($mvalue[0]) && isset($mvalue[0][0])
                                    && $mop[0][0] == $mvalue[0][0]){
                                        $result[$attributeCode]['childOptions'][] = [
                                            'attrId' => $_op['attrId'],
                                            'attribute_value' => 'root_color_hair_length',
                                            'optionTitle' => $_op['optionTitle'],
                                            'priceRule' => 0,
                                            'priceRuleId' =>  0,
                                            'range' => null,
                                            'rangeUnit' => null,
                                        ];
                                        break;
                                    }
                                }
                            }

                        }
                    }
                    break;

                case 'm_grey_hair':
                    if(in_array($title, [
                        'Grey hair',
                        'Grey hair inner',
                        'Grey Hair Type',
                        'Grey Hair Percentage',
                        '1. Front',
                        '2. Top',
                        '3. Crown',
                        '4. Back',
                        '5,6. Temples',
                        '7,8. Sides',
                        'Front',
                        'Top',
                        'Crown',
                        'Back',
                        'Temples',
                        'Sides',
                    ])) {
                        if(!isset($result[$attributeCode])) {
                            $result[$attributeCode] = [
                                'attrId' => 0,
                                'attribute_value' => $attributeCode,
                                'optionTitle' => '',
                                'priceRule' => 0,
                                'priceRuleId' => 0,
                                'childOptions' => []
                            ];
                        }
                        if($title == 'Grey hair') {
                            $isAttributeExist = $product->getResource()->getAttribute($attributeCode);
                            $optionId = $isAttributeExist->getSource()->getOptionId(trim($value));
                            $result[$attributeCode]['attrId'] = $optionId;
                            $result[$attributeCode]['optionTitle'] = $value;
                        }else if($title == 'Grey Hair Type') {
                            $this->customizationHelper->setAttrOptionValues('m_grey_hair_type');
                            $stepOption = $this->customizationHelper->getCommonListItem(1113,'m_grey_hair_type');
                            if($stepOption && $stepOption['childOption']) {
                                $isAttributeExist = $product->getResource()->getAttribute('m_grey_hair_type');
                                foreach($stepOption['childOption'] as $_opt) {
                                    if($_opt['optionTitle'] == trim($value)) {
                                        $optionId = $isAttributeExist->getSource()->getOptionId(trim($value));
                                        $result[$attributeCode]['childOptions'][] = [
                                            "attrId" =>  $optionId,
                                            'attribute_value' => $stepOption['attribute_value'],
                                            'optionTitle' => $_opt['optionTitle'],
                                            'priceRule' => isset($upgradePriceOptions[trim($value)]) ?
                                                $upgradePriceOptions[trim($value)]['price'] : 0,
                                            'priceRuleId' =>   isset($upgradePriceOptions[trim($value)]) ?
                                                $upgradePriceOptions[trim($value)]['id'] : 0,
                                            'range' => null,
                                            'rangeUnit' => null,
                                        ];
                                    }
                                }
                            }
                        }else if(in_array($title, [
                            '1. Front',
                            '2. Top',
                            '3. Crown',
                            '4. Back',
                            '5,6. Temples',
                            '7,8. Sides',
                            'Front',
                            'Top',
                            'Crown',
                            'Back',
                            'Temples',
                            'Sides'
                        ]) && strpos($value, '%') !== false) {
                            $stepOption = $this->customizationHelper->getFTCBTOptions('m_grey_hair_length');
                            if($stepOption && $stepOption['childOption']) {
                                foreach($stepOption['childOption'] as $_opt) {
                                    if(strpos(strtolower($title), strtolower($_opt['optionTitle'])) !== false) {
                                        $result[$attributeCode]['childOptions'][] = [
                                            "attrId" =>  $_opt['attrId'],
                                            'attribute_value' => $_opt['attribute_value'],
                                            'optionTitle' => $_opt['optionTitle'],
                                            'priceRule' => 0,
                                            'priceRuleId' => 0,
                                            'range' => str_replace('%', '', $value),
                                            'rangeUnit' => 'percentage',
                                        ];
                                    }
                                }
                            }
                        }
                    }
                    break;

                case  'm_hair_cut_styled_have':
                    if(in_array($title, [
                        'Hair Cut',
                        'Hair cut',
                        'Cut Style My Length',
                        'Cut-in & Style',
                        'Cut style',
                        'Hair Cut Length 1. Front',
                        '1. Front',
                        'Hair Cut Length 2. Top',
                        '2. Top',
                        'Hair Cut Length 3. Crown',
                        '3. Crown',
                        'Hair Cut Length 4. Back',
                        '4. Back',
                        'Hair Cut Length 5,6. Temples',
                        '5,6. Temples',
                        'Hair Cut Length 7,8. Sides',
                        '7,8. Sides',
                        'Hair Cut Additional Instruction',
                    ])) {
                        if(!isset($result[$attributeCode])) {
                            $result[$attributeCode] = [
                                'attrId' => 0,
                                'attribute_value' => $attributeCode,
                                'optionTitle' => '',
                                'priceRule' => 0,
                                'priceRuleId' => 0,
                                'childOptions' => []
                            ];
                        }
                        if(in_array($title, [
                            'Cut Style My Length',
                        ])) {
                            $this->customizationHelper->setAttrOptionValues($attributeCode);
                            $stepOption = $this->customizationHelper->getHairCutOption(15,$attributeCode);
                            if($stepOption && $stepOption['childOption']) {
                                $isAttributeExist = $product->getResource()->getAttribute($attributeCode);
                                foreach($stepOption['childOption'] as $_opt) {
                                    if($_opt['optionTitle'] == trim($value)) {
                                        $optionId = $isAttributeExist->getSource()->getOptionId(trim($value));
                                        $result[$attributeCode]['optionTitle'] = $_opt['optionTitle'];
                                        $result[$attributeCode]['attrId'] = $optionId;
                                        $result[$attributeCode]['priceRule'] = isset($upgradePriceOptions[trim($value)]) ?
                                            $upgradePriceOptions[trim($value)]['price'] : 0;
                                        $result[$attributeCode]['priceRuleId'] = isset($upgradePriceOptions[trim($value)]) ?
                                            $upgradePriceOptions[trim($value)]['id'] : 0;
                                        break;
                                    }
                                }
                            }
                        }else if(in_array($title, [
                            'Cut-in & Style',
                            'Cut style',
                        ])) {
                            $this->customizationHelper->setAttrOptionValues('m_cut_style');
                            $stepOption = $this->customizationHelper->getCommonListItem(131,'m_cut_style');
                            if($stepOption && $stepOption['childOption']) {
                                $isAttributeExist = $product->getResource()->getAttribute('m_cut_style');
                                foreach($stepOption['childOption'] as $_opt) {
                                    if($_opt['optionTitle'] == trim($value)) {
                                        $optionId = $isAttributeExist->getSource()->getOptionId(trim($value));
                                        $result[$attributeCode]['childOptions'][] = [
                                            "attrId" =>  $optionId,
                                            'attribute_value' => $stepOption['attribute_value'],
                                            'optionTitle' => $_opt['optionTitle'],
                                            'priceRule' => isset($upgradePriceOptions[trim($value)]) ?
                                                $upgradePriceOptions[trim($value)]['price'] : 0,
                                            'priceRuleId' =>   isset($upgradePriceOptions[trim($value)]) ?
                                                $upgradePriceOptions[trim($value)]['id'] : 0,
                                            'range' => null,
                                            'rangeUnit' => null,
                                        ];
                                        break;
                                    }
                                }
                            }
                        }else if(in_array($title, [
                            'Hair Cut Length 1. Front',
                            '1. Front',
                            'Hair Cut Length 2. Top',
                            '2. Top',
                            'Hair Cut Length 3. Crown',
                            '3. Crown',
                            'Hair Cut Length 4. Back',
                            '4. Back',
                            'Hair Cut Length 5,6. Temples',
                            '5,6. Temples',
                            'Hair Cut Length 7,8. Sides',
                            '7,8. Sides',
                        ]) && strpos($value, 'inch') !== false) {
                            $stepOption = $this->customizationHelper->getFTCBTOptions('m_hair_cut_order_length');
                            if($stepOption && $stepOption['childOption']) {
                                foreach($stepOption['childOption'] as $_opt) {
                                    if(strpos($value, $_opt['optionTitle']) !== false) {
                                        $result[$attributeCode]['childOptions'][] = [
                                            "attrId" =>  $_opt['attrId'],
                                            'attribute_value' => $_opt['attribute_value'],
                                            'optionTitle' => $_opt['optionTitle'],
                                            'priceRule' =>  0,
                                            'priceRuleId' =>  0,
                                            'range' => str_replace('inch', '', trim(strtolower($value))),
                                            'rangeUnit' => 'inch',
                                        ];
                                        break;
                                    }
                                }
                            }
                        }
                    }
                    break;

                default:
                    if($title == $attributeLabel ||
                        ($attributeLabel == 'Production Time' && in_array($attributeLabel, [
                                'Rush service'
                            ]))) {
                        if(in_array(trim($value), [
                            'European hair (fine, thin & soft, 7" and up is not available)',
                        ])) {
                            $value = 'European hair (fine&comma; thin & soft&comma; 7" and up is not available)';
                        }
                        $isAttributeExist = $product->getResource()->getAttribute($attributeCode);
                        if(strtolower($title) == 'hair length') {
                            preg_match_all('!\d+!', $value, $matches);
                            $value  = 'Longest at '.$matches[0][0].'"';
                            $attrId = $isAttributeExist->getSource()->getOptionId(trim($value));
                            $value = $matches[0][0] . ' inch ('.number_format($matches[0][0] * 2.54, 2).' cm)';
                        }else{
                            $attrId = $isAttributeExist->getSource()->getOptionId(trim($value));
                        }

                        $result[$attributeCode] = [
                            'attrId' => $attrId ? $attrId : $attrIds[$attributeCode],
                            'attribute_value' => $attributeCode,
                            'optionTitle' => trim($value),
                            'priceRule' => 0,
                            'priceRuleId' => 0,
                            'childOptions' => []
                        ];

                        $result[$attributeCode]['priceRule'] = isset($upgradePriceOptions[trim($value)]) ?
                            $upgradePriceOptions[trim($value)]['price'] : 0;
                        $result[$attributeCode]['priceRuleId'] = isset($upgradePriceOptions[trim($value)]) ?
                            $upgradePriceOptions[trim($value)]['id'] : 0;
                    }
            }
        }

        if($attributeCode == 'm_base_size_section' &&
            isset($result[$attributeCode]) &&
            $result[$attributeCode]['optionTitle'] == 'Topper' &&
            $baseSizeTopperArea > 1) {
            $upgradePriceLabel = '';
            switch (true) {
                case $baseSizeTopperArea > 70 && $baseSizeTopperArea <= 80:
                    $upgradePriceLabel = CustomizationHelper::Prices_Base_Size[0];
                    break;
                case $baseSizeTopperArea > 80 && $baseSizeTopperArea <= 100:
                    $upgradePriceLabel = CustomizationHelper::Prices_Base_Size[1];
                    break;
                case $baseSizeTopperArea > 100:
                    $upgradePriceLabel = CustomizationHelper::Prices_Base_Size[2];
                    break;
            }
            if($upgradePriceLabel) {
                $result[$attributeCode]['priceRule'] = isset($upgradePriceOptions[$upgradePriceLabel]) ?
                    $upgradePriceOptions[$upgradePriceLabel]['price'] : 0;
                $result[$attributeCode]['priceRuleId'] = isset($upgradePriceOptions[$upgradePriceLabel]) ?
                    $upgradePriceOptions[$upgradePriceLabel]['id'] : 0;
            }
        }

        return $result;
    }

    /**
     * Add order line item error
     *
     * @param string $message
     * @param string|null $code
     * @return void
     */
    private function addError(string $message, string $code = null): void
    {
        $this->errors[] = new \Magento\Sales\Model\Reorder\Data\Error(
            $message,
            $code ?? $this->getErrorCode($message)
        );
    }

    /**
     * Get message error code. Ad-hoc solution based on message parsing.
     *
     * @param string $message
     * @return string
     */
    private function getErrorCode(string $message): string
    {
        $code = self::ERROR_UNDEFINED;

        $matchedCodes = array_filter(
            self::MESSAGE_CODES,
            function ($key) use ($message) {
                return false !== strpos($message, $key);
            },
            ARRAY_FILTER_USE_KEY
        );

        if (!empty($matchedCodes)) {
            $code = current($matchedCodes);
        }

        return $code;
    }

    /**
     * Prepare output
     *
     * @param CartInterface $cart
     * @return \Magento\Sales\Model\Reorder\Data\ReorderOutput
     */
    private function prepareOutput(CartInterface $cart): \Magento\Sales\Model\Reorder\Data\ReorderOutput
    {
        $output = new \Magento\Sales\Model\Reorder\Data\ReorderOutput($cart, $this->errors);
        $this->errors = [];
        // we already show user errors, do not expose it to cart level
        $cart->setHasError(false);
        return $output;
    }

    /**
     * Get error message for a cart item
     *
     * @param Item $item
     * @param Product $product
     * @param string|null $message
     * @return string
     */
    private function getCartItemErrorMessage(Item $item, Product $product, string $message = null): string
    {
        // try to get sku from line-item first.
        // for complex product type: if custom option is not available it can cause error
        $sku = $item->getSku() ?? $product->getData('sku');
        return (string)($message
            ? __('Could not add the product with SKU "%1" to the shopping cart: %2', $sku, $message)
            : __('Could not add the product with SKU "%1" to the shopping cart', $sku));
    }
}
