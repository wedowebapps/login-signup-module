<?php
namespace Lordhair\Customizations\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;

class InstallData implements InstallDataInterface
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

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        /**
         * Add attributes to the eav_attribute
         */

        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'm_base_size_section',
            [
                'group' => 'Options Config',
                'attribute_set' => 'Default Migrated',
                'type' => 'text',
                'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
                'frontend' => '',
                'label' => 'I′ll fill the dimension in below Base Size section',
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

        $attributeId = $eavSetup->getAttributeId('catalog_product', 'm_base_size_section');

        $options = [
            'values' => [
                '1' => 'Topper',
                '2' => 'Full Cap'
            ],
            'attribute_id' => $attributeId,
        ];

        $eavSetup->addAttributeOption($options);

        //Create m_highlights_type_new Attribute
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'm_highlights_type_new',
            [
                'group' => 'Options Config',
                'attribute_set' => 'Default Migrated',
                'type' => 'text',
                'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
                'frontend' => '',
                'label' => 'I want highlights',
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

        $attributeId = $eavSetup->getAttributeId('catalog_product', 'm_highlights_type_new');

        $options = [
            'values' => [
                '1' => 'Match the sample I\'ll send in',
                '2' => 'Match the sample already on file',
                '3' => 'Same as my last order',
                '4' => 'Evenly Blended',
                '5' => 'Spot/Dot',
                '6' => 'Please refer to my specific instructions'
            ],
            'attribute_id' => $attributeId,
        ];

        $eavSetup->addAttributeOption($options);

        //Create m_want_grey_hair Attribute
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'm_want_grey_hair',
            [
                'group' => 'Options Config',
                'attribute_set' => 'Default Migrated',
                'type' => 'text',
                'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
                'frontend' => '',
                'label' => 'I want grey hair',
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

        $attributeId = $eavSetup->getAttributeId('catalog_product', 'm_want_grey_hair');

        $options = [
            'values' => [
                '1' => 'How much grey hair do you need?',
                '2' => 'Choose grey hair type',
            ],
            'attribute_id' => $attributeId,
        ];

        $eavSetup->addAttributeOption($options);

        //Create m_how_much_grey_hair Attribute
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'm_how_much_grey_hair',
            [
                'group' => 'Options Config',
                'attribute_set' => 'Default Migrated',
                'type' => 'text',
                'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
                'frontend' => '',
                'label' => 'How much grey hair do you need?',
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

        $attributeId = $eavSetup->getAttributeId('catalog_product', 'm_how_much_grey_hair');

        $options = [
            'values' => [
                '1' => 'Same as grey percentage sample I\'ll send in',
                '2' => 'Same as my last order/sample already on file',
                '3' => 'Customize my grey distribution and percentage',
            ],
            'attribute_id' => $attributeId,
        ];

        $eavSetup->addAttributeOption($options);
    }
}