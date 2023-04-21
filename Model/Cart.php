<?php
namespace Lordhair\Customizations\Model;

class Cart extends \Magento\Checkout\Model\Cart
{
    /**
     * Convert order item to quote item
     *
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @param true|null $qtyFlag if is null set product qty like in order
     * @return $this
     */
    public function addOrderItem($orderItem, $qtyFlag = null)
    {

        /* @var $orderItem \Magento\Sales\Model\Order\Item */
        if ($orderItem->getParentItem() === null) {
            $storeId = $this->_storeManager->getStore()->getId();
            try {
                /**
                 * We need to reload product in this place, because products
                 * with the same id may have different sets of order attributes.
                 */
                $product = $this->productRepository->getById($orderItem->getProductId(), false, $storeId, true);
            } catch (NoSuchEntityException $e) {
                return $this;
            }

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
            $info = $orderItem->getProductOptionByCode('info_buyRequest');
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
                    if(is_string($option) && strpos($option, ';') !== false) {
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
                        $newOption = str_replace('Average service 10-14 weeks', 'Regular service 8-12 weeks', $newOption);
                        $newOption = str_replace('Average service At least 12 weeks', 'Regular service 8-12 weeks', $newOption);
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
                }
            }

            $info = new \Magento\Framework\DataObject($info);
            if ($qtyFlag === null) {
                $info->setQty($orderItem->getQtyOrdered());
            } else {
                $info->setQty(1);
            }
            $this->addProduct($product, $info);
        }
        return $this;
    }
}
