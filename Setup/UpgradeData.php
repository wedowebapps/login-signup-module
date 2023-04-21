<?php
namespace Lordhair\LoginSignup\Setup;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

class UpgradeData implements UpgradeDataInterface
{
    const COUNTRY_ISO_CODE = 'loginsignup_countryisocode';

    private $eavSetupFactory;

    private $eavConfig;

    public function __construct(EavSetupFactory $eavSetupFactory,\Magento\Eav\Model\Config $eavConfig)
    {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavConfig = $eavConfig;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context) {
        
        if(version_compare($context->getVersion(), '1.0.1', '<')) {

            $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
            $setup->startSetup();

            $attributeCode = self::COUNTRY_ISO_CODE;
            $eavSetup->addAttribute(CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER, $attributeCode, [
                'type'     => 'varchar',
                'label'     => 'Counrty ISO Code',
                'visible'   => true,
                'required'  => false,
                'user_defined' => 1,
                'system'    => 0,
                'position'  => 102,
                'unique'    => false,
                'input'     => 'text'
            ]);
    
            $eavSetup->addAttributeToSet(
                CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
                CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER,
                null,
                $attributeCode);
    
            $countryCode = $this->eavConfig->getAttribute(CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER, $attributeCode);
            $countryCode->setData('used_in_forms', [
                'adminhtml_customer',
                'customer_account_create',
                'customer_account_edit'
            ]);
            $countryCode->getResource()->save($countryCode);
        }
	}
}
