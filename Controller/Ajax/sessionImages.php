<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Lordhair\Customizations\Controller\Ajax;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Session\SaveHandler;
use Magento\Store\Model\StoreManagerInterface;

/**
 * SessionImages controller
 */
class SessionImages extends \Magento\Framework\App\Action\Action
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
     * @var Magento\Catalog\Model\ProductFactory $productloader
     */
    protected $_productloader;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface $_coreSession
     */
    protected $_coreSession;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Initialize SessionImages controller
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\SessionFactory $SessionFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        PageFactory $resultPageFactory,
        FilterProvider $filterProvider,
        StoreManagerInterface $storeManager,
        SessionManagerInterface $coreSession
    ) {
       
        $this->customerSession = $SessionFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->_resultPageFactory = $resultPageFactory;
        $this->_coreSession = $coreSession;
        $this->_storeManager = $storeManager;
        parent::__construct($context);
    }

    public function execute()
    {
        $httpBadRequestCode = 400;
        $resultJson = $this->resultJsonFactory->create();
        $resultRaw = $this->resultRawFactory->create();

        try {
            $getPostParams = [
                'pId' => $this->getRequest()->getPost('pid'),
            ];
        } catch (\Exception $e) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }

        if (!$getPostParams || $this->getRequest()->getMethod() !== 'GET' || !$this->getRequest()->isXmlHttpRequest()) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }

        $response = [
            'errors' => false,
            'data' => array(),
            'message' => __('Success')
        ];

        try {

            $hairCutImages = $this->getSessionHairCutImagesValue();
            $newHairCutImages = array();
            if (is_array($hairCutImages)) {
                foreach ($hairCutImages as $image) {
                    $newImage = explode("//",$image);
                    $uploadDirectory = 'media/customization/haircut/'.$newImage[0].'/'.$newImage[1];
                    $target = $this->getSiteMainUrl().$uploadDirectory;
                    $newHairCutImages[] = array(
                        'name' => $newImage[1],
                        'uuid' => $newImage[0],
                        'thumbnailUrl' => $target
                    );
                }
            }
            return $resultJson->setData($newHairCutImages);

        } catch (LocalizedException $e) {

            $errorMessage = $e->getMessage();
            $successStatus = false;
            return $resultJson->setData([
                'success'    => $successStatus,
                'error'      => $errorMessage
            ]);
            
        } catch (\Exception $e) {

            $errorMessage = $e->getMessage();
            $successStatus = false;
            return $resultJson->setData([
                'success'    => $successStatus,
                'error'      => $errorMessage
            ]);
        }
    }

    public function getSiteMainUrl()
    {
        $getSiteMainUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK);
        return $getSiteMainUrl;
    }

    public function getSessionHairCutImagesValue(){
        $this->_coreSession->start();
        return $this->_coreSession->getHairCutImages();
    }
}