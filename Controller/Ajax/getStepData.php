<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Lordhair\Customizations\Controller\Ajax;

use Magento\Framework\Exception\LocalizedException;
use Magento\Catalog\Model\Session as CatalogSession;
use Magento\Framework\View\Result\PageFactory;
use Lordhair\Customizations\Helper\Data as CustomizationHelper;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Model\ProductFactory;

/**
 * GetStepData controller
 */
class GetStepData extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @var Lordhair\Customizations\Helper\Data $CustomizationHelper
     */
    protected $_customizationHelper;

    /**
     * @var Magento\Catalog\Model\ProductFactory $productloader
     */
    protected $_productloader;

    /**
     * Initialize GetAllSteps controller
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        JsonFactory $resultJsonFactory,
        RawFactory $resultRawFactory,
        PageFactory $resultPageFactory,
        CustomizationHelper $customizationHelper,
        ProductFactory $productloader
    ) {
       
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->_resultPageFactory = $resultPageFactory;
        $this->_customizationHelper = $customizationHelper;
        $this->_productloader = $productloader;
        parent::__construct($context);
    }

    public function execute()
    {
        $httpBadRequestCode = 400;
        $resultRaw = $this->resultRawFactory->create();

        try {
            $getPostParams = [
                'currentStepCode'   => $this->getRequest()->getPost('currentStepCode'),
                'pId'               => $this->getRequest()->getPost('pId')
            ];
        } catch (\Exception $e) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }

        if (!$getPostParams || $this->getRequest()->getMethod() !== 'POST' || !$this->getRequest()->isXmlHttpRequest()) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }

        $response = [
            'errors' => false,
            'data' => array(),
            'message' => __('Success')
        ];

        try {
            $pId = $getPostParams['pId'];
            $currentStepCode = $getPostParams['currentStepCode'];

            $product = $this->_productloader->create()->load($pId);
            $storeId = $this->_customizationHelper->getStore()->getId();

            $leftSideMainHtml = $this->_customizationHelper->getLeftSideMainHtml($product,$currentStepCode);

            $response['data'] = array (
                'leftBgImage'       => $this->getSiteMainUrl().'media/catalog/product' . $product->getImage(),
                'leftSideMainHtml'  => $leftSideMainHtml['html'],
                'finalPrice'  => $leftSideMainHtml['finalPrice'],
                'upgradedPrice'  => $leftSideMainHtml['upgradedPrice']
            ); 
            
        } catch (LocalizedException $e) {
            $response = [
                'errors' => true,
                'errorType' => 'localized',
                'message' => $e->getMessage()
            ];
        } catch (\Exception $e) {
            $response = [
                'errors' => true,
                'errorType' => 'exception',
                'message' => __('Invalid product please try agian later or contact store admin. ').$e->getMessage()
            ];
        }
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($response);
    }

    public function getSiteMainUrl()
    { 
        return $this->_customizationHelper->getSiteMainUrl();
    }
}