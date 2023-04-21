<?php
namespace Lordhair\Customizations\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;

use Magento\Framework\Setup\UpgradeDataInterface;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * Init
     *
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context) {
        
        if(version_compare($context->getVersion(), '1.0.1', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

            //Create m_hair_cut_styled_have Attribute
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'm_hair_cut_styled_have',
                [
                    'group' => 'Options Config',
                    'attribute_set' => 'Default Migrated',
                    'type' => 'text',
                    'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
                    'frontend' => '',
                    'label' => 'Yes, have hair cut-in and styled',
                    'input' => 'multiselect',
                    'class' => '',
                    'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => true,
                    'is_used_in_grid' => true,
                    'visible_on_front' => true,
                    'used_in_product_listing' => true,
                    'unique' => false
                ]
            );

            $attributeId = $eavSetup->getAttributeId('catalog_product', 'm_hair_cut_styled_have');

            $options = [
                'values' => [
                '1' => 'Choose your hairstyles',
                '2' => 'I want to order my length',
                '3' => 'I\'ll send email to Lordhair',
                '4' => 'Upload hairstyle images you want',
            ],
            'attribute_id' => $attributeId,
            ];

            $eavSetup->addAttributeOption($options);
        }

        if(version_compare($context->getVersion(), '1.0.2', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

            //Create m_base_production_time Attribute
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'm_base_production_time',
                [
                    'group' => 'Options Config',
                    'attribute_set' => 'Default Migrated',
                    'type' => 'text',
                    'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
                    'frontend' => '',
                    'label' => 'Base Production Time',
                    'input' => 'multiselect',
                    'class' => '',
                    'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => true,
                    'is_used_in_grid' => true,
                    'visible_on_front' => true,
                    'used_in_product_listing' => true,
                    'unique' => false
                ]
            );

            $attributeId = $eavSetup->getAttributeId('catalog_product', 'm_base_production_time');

            $options = [
                'values' => [
                '1' => 'Regular service 9-10 weeks',
                '2' => 'Rush service 7-8 weeks'
            ],
            'attribute_id' => $attributeId,
            ];

            $eavSetup->addAttributeOption($options);
        }
	}
}
