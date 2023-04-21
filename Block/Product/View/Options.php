<?php
namespace Lordhair\Customizations\Block\Product\View;

class Options extends \Magento\Catalog\Block\Product\View\Options
{

    public function getGroupAttributes($pro,$isCustom = false,$groupId = 36){

        $data = [];
        $ignoreAttr = array(
            'Hair color option out stock',
            'Hair Color For Out Stock',
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
            'Tape width'
        );

        $productAttributes = $pro->getAttributes();
        foreach ($productAttributes as $attribute){
            if ($attribute->isInGroup($pro->getAttributeSetId(), $groupId)){
                $_code = $attribute->getAttributeCode();
                $getSelectedValues = $pro->getResource()->getAttribute($_code)->getFrontend()->getValue($pro);
                if ($getSelectedValues && $getSelectedValues != '') {
                    $getSelectedValues = explode(",",$getSelectedValues);
                    if ($getSelectedValues && is_array($getSelectedValues) && count($getSelectedValues) > 0 && !in_array($attribute->getStoreLabel(), $ignoreAttr)) {
                        $data[] = $attribute->getStoreLabel().':';
                    }
                }
            }
        }
        return $data;
    }
}