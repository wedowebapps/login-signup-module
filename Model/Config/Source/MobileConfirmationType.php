<?php 

namespace Lordhair\LoginSignup\Model\Config\Source;
 
class MobileConfirmationType extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ['value' => '0', 'label' => __('No')],
                ['value' => '1', 'label' => __('Yes')]
            ];
        }
        return $this->_options;
    }
 
    /**
     * Get text of the option value
     * 
     * @param string|integer $value
     * @return string|bool
     */
    public function getOptionValue($value) 
    { 
        foreach ($this->getAllOptions() as $option) {
            if ($option['value'] == $value) {
                return $option['label'];
            }
        }
        return false;
    }
}