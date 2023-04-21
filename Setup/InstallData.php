<?php

namespace Lordhair\LoginSignup\Setup;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface 
{
    const PHONE_NUMBER = 'loginsignup_telephone';

    const COUNTRY_CODE = 'loginsignup_countrycode';

    const MOBILE_CONFIRMATION = 'loginsignup_mobileconfirmation';

    const OTP_VERIFICATION = 'loginsignup_otpverificationcode';

    const PHONE_NUMBER_CODE = 'loginsignup_telephonewithcode';

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;
    /**
     * @var \Magento\Eav\Model\Config
     */
    private $eavConfig;

    public function __construct(
        EavSetupFactory $eavSetupFactory,
        \Magento\Eav\Model\Config $eavConfig
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavConfig = $eavConfig;
    }

    /**
     * Installs data for a module
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /** @var \Magento\Eav\Setup\EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        $setup->startSetup();
        
        /**  @var Create telephone attribute */
        $attributeCode = self::PHONE_NUMBER;
        $eavSetup->addAttribute(CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER, $attributeCode, [
            'type'     => 'varchar',
            'label'     => 'Telephone',
            'visible'   => false,
            'required'  => false,
            'user_defined' => 1,
            'system'    => 0,
            'position'  => 103,
            'unique'    => false,
            'input'     => 'text'
        ]);

        $eavSetup->addAttributeToSet(
            CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
            CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER,
            null,
            $attributeCode);

        $telephone = $this->eavConfig->getAttribute(CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER, $attributeCode);
        $telephone->setData('used_in_forms', [
            'adminhtml_customer',
            'customer_account_create',
            'customer_account_edit'
        ]);
        $telephone->getResource()->save($telephone);

        /**  @var Create CountryCode attribute */
        $attributeCode = self::COUNTRY_CODE;
        $eavSetup->addAttribute(CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER, $attributeCode, [
            'type'     => 'varchar',
            'label'     => 'Counrty Code',
            'visible'   => true,
            'required'  => false,
            'user_defined' => 1,
            'system'    => 0,
            'position'  => 104,
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

        /**  @var Create telephone with code attribute */
        $attributeCode = self::PHONE_NUMBER_CODE;
        $eavSetup->addAttribute(CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER, $attributeCode, [
            'type'          => 'varchar',
            'label'         => 'FullTelephone',
            'visible'       => false,
            'required'      => false,
            'user_defined'  => 1,
            'system'        => 0,
            'position'      => 105,
            'unique'        => false,
            'input'         => 'text'
        ]);

        $eavSetup->addAttributeToSet(
            CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
            CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER,
            null,
            $attributeCode);

        $telephoneCode = $this->eavConfig->getAttribute(CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER, $attributeCode);
        $telephoneCode->setData('used_in_forms', [
            'adminhtml_customer',
            'customer_account_create',
            'customer_account_edit'
        ]);
        $telephoneCode->getResource()->save($telephoneCode);

        /**  @var Create MOBILE_CONFIRMATION attribute */
        $attributeCode = self::MOBILE_CONFIRMATION;
        $eavSetup->addAttribute(CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER, $attributeCode, [
            'type'          => 'int',
            'label'         => 'Mobile Confirmation',
            'visible'       => true,
            'required'      => false,
            'default'       => '0',
            'user_defined'  => 1,
            'system'        => 0,
            'position'      => 106,
            'unique'        => false,
            'input'         => 'select',
            'source'        => 'Lordhair\LoginSignup\Model\Config\Source\MobileConfirmationType',
        ]);

        $eavSetup->addAttributeToSet(
            CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
            CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER,
            null,
            $attributeCode);

        $mobileConfirmation = $this->eavConfig->getAttribute(CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER, $attributeCode);
        $mobileConfirmation->setData('used_in_forms', [
            'adminhtml_customer',
            'customer_account_create',
            'customer_account_edit'
        ]);
        $mobileConfirmation->getResource()->save($mobileConfirmation);

        /**  @var Create OTP_VERIFICATION attribute */
        $attributeCode = self::OTP_VERIFICATION;
        $eavSetup->addAttribute(CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER, $attributeCode, [
            'type'          => 'varchar',
            'label'         => 'OTP Verification',
            'visible'       => true,
            'required'      => false,
            'user_defined'  => 1,
            'system'        => 0,
            'position'      => 107,
            'unique'        => false,
            'input'         => 'text'
        ]);

        $eavSetup->addAttributeToSet(
            CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
            CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER,
            null,
            $attributeCode);

        $otpVerification = $this->eavConfig->getAttribute(CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER, $attributeCode);
        $otpVerification->setData('used_in_forms', [
            'adminhtml_customer',
            'customer_account_create',
            'customer_account_edit'
        ]);
        $otpVerification->getResource()->save($otpVerification);
        
        //add new option to gender
        $tableOptions        = $setup->getTable('eav_attribute_option');
        $tableOptionValues   = $setup->getTable('eav_attribute_option_value');

        // add options for level of politeness
        $attributeId = (int)$eavSetup->getAttribute('customer', 'gender', 'attribute_id');

        // add option
        $data = array(
            'attribute_id' => $attributeId,
            'sort_order'   => 99,
        );
        $setup->getConnection()->insert($tableOptions, $data);

        // add option label
        $optionId = (int)$setup->getConnection()->lastInsertId($tableOptions, 'option_id');
        $data = array(
            'option_id' => $optionId,
            'store_id'  => 0,
            'value'     => 'Other',
        );
        $setup->getConnection()->insert($tableOptionValues, $data);

        $setup->endSetup();
    }
}