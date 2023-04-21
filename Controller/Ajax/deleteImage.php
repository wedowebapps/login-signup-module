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
use Magento\Framework\Filesystem\Driver\File;

/**
 * DeleteImage controller
 */
class DeleteImage extends \Magento\Framework\App\Action\Action
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
     * @var \Magento\Framework\Filesystem\Driver\File $_file
     */
    protected $_file;

    /**
     * Initialize DeleteImage controller
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
        SaveHandler $sessionHandler,
        File $file
    ) {
       
        $this->customerSession = $SessionFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->_resultPageFactory = $resultPageFactory;
        $this->_customer        = $customerFactory;
        $this->_productloader   = $productloader;
        $this->_filterProvider  = $filterProvider;
        $this->fileUploader     = $fileUploader;
        $this->_coreSession     = $coreSession;
        $this->_sessionHandler  = $sessionHandler;
        $this->_file            = $file;
        $this->directory        = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
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
                'pId' => $this->getRequest()->getPost('pid'),
            ];
        } catch (\Exception $e) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }

        if (!$getPostParams || $this->getRequest()->getMethod() !== 'DELETE' || !$this->getRequest()->isXmlHttpRequest()) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }

        $response = [
            'errors' => false,
            'data' => array(),
            'message' => __('Success')
        ];

        try {

            $pId = $getPostParams['pId'];
            $getUriString = $this->getRequest()->getUriString();
            $qquuid = substr($getUriString, strrpos($getUriString, 'Image/' )+6);
            $uploadDirectory = 'customization/haircut/'.$qquuid.'/';
            $target = $this->mediaDirectory->getAbsolutePath($uploadDirectory);
            $hairCutImages = $this->getSessionHairCutImagesValue();
            $imageName = '';
            foreach ($hairCutImages as $key => $image){
                if(preg_match('/' . $qquuid . '/i', $image)){
                    $imageName = substr($image, strrpos($image, '//' )+2);
                    if ($this->_file->isExists($target . $imageName)) {
                        $this->_file->deleteFile($target . $imageName);
                        $this->directory->delete($uploadDirectory);
                    }
                    unset($hairCutImages[$key]);
                    sort($hairCutImages);
                }
            }
            $this->setSessionValue($hairCutImages);
            $successStatus = true;
            return $resultJson->setData([
                'success'    => $successStatus,
                'error'      => '',
                'imageName' => $imageName,
                'hairCutImages'  => $this->getSessionHairCutImagesValue(),
                'uuid'       => $qquuid
            ]);

        } catch (LocalizedException $e) {
            $errorMessage = $e->getMessage();
            $successStatus = false;
            return $resultJson->setData([
                'success'    => $successStatus,
                'error'      => $errorMessage
            ]);
            
        } catch (\Exception $e) {
            $errorMessage = new LocalizedException(__('Unable to submit your request. Please, try again later'));
            $successStatus = false;
            return $resultJson->setData([
                'success'    => $successStatus,
                'error'      => $errorMessage
            ]);
        }
    }

    public function setSessionValue($hairCutImages){
        $this->_coreSession->setHairCutImages($hairCutImages);
    }

    public function getSessionHairCutImagesValue(){
        $this->_coreSession->start();
        return $this->_coreSession->getHairCutImages();
    }
}