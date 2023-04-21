<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Lordhair\Customizations\Controller\Ajax;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Customer\Model\CustomerFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Session\SaveHandler;

/**
 * UploadImages controller
 */
class UploadImages extends \Magento\Framework\App\Action\Action
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
     * @var Magento\Catalog\Model\ProductFactory $filterProvider
     */
    protected $_filterProvider;

    /**
     * @var \Magento\Framework\Filesystem $filesystem
     */
    protected $filesystem;
 
    /**
     * @var \Magento\MediaStorage\Model\File\UploaderFactory $fileUploader
     */
    protected $fileUploader;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface $_coreSession
     */
    protected $_coreSession;

    /**
     * @var \Magento\Framework\Session\SaveHandler $_sessionHandler
     */
    protected $_sessionHandler;

    /**
     * Initialize UploadImages controller
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\SessionFactory $SessionFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        PageFactory $resultPageFactory,
        CustomerFactory $customerFactory,
        ProductFactory $productloader,
        FilterProvider $filterProvider,
        Filesystem $filesystem,
        UploaderFactory $fileUploader,
        SessionManagerInterface $coreSession,
        SaveHandler $sessionHandler
    ) {
       
        $this->customerSession = $SessionFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->_resultPageFactory = $resultPageFactory;
        $this->_customer = $customerFactory;
        $this->_productloader = $productloader;
        $this->_filterProvider = $filterProvider;
        $this->_coreSession      = $coreSession;
        $this->fileUploader      = $fileUploader;
        $this->_sessionHandler   = $sessionHandler;
        $this->mediaDirectory    = $filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        parent::__construct($context);
    }

    public function execute()
    {
        $httpBadRequestCode = 400;
        $resultJson = $this->resultJsonFactory->create();
        $resultRaw = $this->resultRawFactory->create();

        try {
            $getPostParams = [
                'pId'           => $this->getRequest()->getPost('pid'),
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

            $hairCutImages = $this->getSessionHairCutImagesValue();
            $pId = $getPostParams['pId'];
            $inputName = 'qqfile';
            $qquuid  = 'qquuid';
            if(!$hairCutImages){
                $hairCutImages = array();
            }
            $file = $this->getRequest()->getFiles($inputName);
            $qquuid = $this->getRequest()->getParam($qquuid);
            $uploadDirectory = 'customization/haircut/'.$qquuid.'/';
            $fileName = ($file && array_key_exists('name', $file)) ? $file['name'] : null;

            if ($file && $fileName) {
                $target = $this->mediaDirectory->getAbsolutePath($uploadDirectory);
                $uploader = $this->fileUploader->create(['fileId' => $inputName]);
                $uploader->setAllowedExtensions(['jpg', 'png', 'bmp', 'png', 'jpeg', 'mp4']);
                $uploader->setAllowCreateFolders(true);
                $uploader->setAllowRenameFiles(true);
                $result = $uploader->save($target);
                if ($result['file']) {
                    $result["uploadName"] = $uploader->getUploadedFileName();
                    array_push($hairCutImages, $qquuid . '//' . $result["uploadName"]);
                    $this->setSessionHairCutImagesValue($hairCutImages);
                    $result["success"] = true;
                    $result["uuid"] = $qquuid;
                    $result["hairCutImages"] = $this->getSessionHairCutImagesValue();
                    return $resultJson->setData($result);
                }
            }

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

    public function setSessionHairCutImagesValue($hairCutImages){
        $this->_coreSession->start();
        $this->_coreSession->setHairCutImages($hairCutImages);
    }

    public function getSessionHairCutImagesValue(){
        $this->_coreSession->start();
        return $this->_coreSession->getHairCutImages();
    }
}