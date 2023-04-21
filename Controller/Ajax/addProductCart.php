<?php

namespace Lordhair\Customizations\Controller\Ajax;

use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Lordhair\Customizations\Helper\ValidateCartItems;

class AddProductCart extends \Magento\Framework\App\Action\Action
{
    protected $resultJsonFactory;
    protected $resultRawFactory;
    protected $productRepository;
    protected $cart;
    protected $catalogHelper;

    public function __construct(
        Context $context,
        CustomerCart $cart,
        JsonFactory $resultJsonFactory,
        RawFactory $resultRawFactory,
        ProductRepositoryInterface $productRepository,
        ValidateCartItems $catalogHelper
    ) {
        $this->cart = $cart;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->productRepository = $productRepository;
        $this->catalogHelper = $catalogHelper;
        parent::__construct($context);
    }

    public function execute()
    {
        try {

            $params = $this->getRequest()->getParams();

            $product = $this->_initProduct();
            $related = $this->getRequest()->getParam('related_product');

            if (isset($params['qty'])) {
                $filter = new \Zend_Filter_LocalizedToNormalized(
                    ['locale' => $this->_objectManager->get(
                        \Magento\Framework\Locale\ResolverInterface::class
                    )->getLocale()]
                );
                $params['qty'] = $filter->filter($params['qty']);
            }

            if (!$product) {
                throw new LocalizedException(__('Product not valid'));
            }

            $this->cart->addProduct($product, $params);

            if (!empty($related)) {
                $this->cart->addProductsByIds(explode(',', $related));
            }

            $this->cart->save();

            $getItemsQty = $this->cart->getQuote()->getItemsQty();

            $veryCartItemsIsRequired = $this->catalogHelper->veryCartItemsIsRequired();

            if(!$veryCartItemsIsRequired) {
                throw new LocalizedException(__('Validation failed'));
            }

            $response = [
                'errors' => false,
                'data' => array(),
                'getItemsQty' => $getItemsQty,
                'message' => __('Success')
            ];

        } catch (LocalizedException $e) {
            $response = [
                'errors' => true,
                'errorType' => 'Localized',
                'message' => $e->getMessage()
            ];
        } catch (\Exception $e) {
            $this->setSessionData('selectedOptions', array());
            $response = [
                'errors' => true,
                'errorType' => 'Exception',
                'message' => __('Invalid product please try agian later or contact store admin. ').$e->getMessage()
            ];
        }
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($response);
    }

    protected function _initProduct()
    {
        $productId = (int)$this->getRequest()->getParam('product');
        if ($productId) {
            $storeId = $this->_objectManager->get(
                \Magento\Store\Model\StoreManagerInterface::class
            )->getStore()->getId();
            try {
                return $this->productRepository->getById($productId, false, $storeId);
            } catch (NoSuchEntityException $e) {
                return false;
            }
        }
        return false;
    }
}