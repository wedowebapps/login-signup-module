<?php
namespace Lordhair\Loginsignup\Block\Form;
class Edit extends \Magento\Customer\Block\Form\Edit{
    public function getLordCustomAttribute($getAttrName)
    {
        if ($this->getCustomer()->getCustomAttribute($getAttrName)) {
            return $this->getCustomer()->getCustomAttribute($getAttrName)->getValue();
        }
        return '';
    }
}