<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Lordhair\LoginSignup\Controller\Ajax;

use Magento\Customer\Model\Session as customerSession;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\App\Action\Context;

/**
 * Login controller
 *
 * @method \Magento\Framework\App\RequestInterface getRequest()
 * @method \Magento\Framework\App\Response\Http getResponse()
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CheckUserExist extends \Magento\Framework\App\Action\Action
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
     * @var StoreManagerInterface
     */
    protected $customerSession;

    /**
     * Initialize Login controller
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $SessionFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     */
    public function __construct(
        Context $context,
        customerSession $customerSession,
        JsonFactory $resultJsonFactory,
        RawFactory $resultRawFactory
    ) {
       
        $this->customerSession = $customerSession;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRawFactory = $resultRawFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $credentials = null;
        $httpBadRequestCode = 400;

        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $resultRaw = $this->resultRawFactory->create();
        
        try {
            $credentials = [
                'checkEmailExist' => $this->getRequest()->getPost('checkEmailExist'),
                'username' => $this->getRequest()->getPost('username'),
                'password' => $this->getRequest()->getPost('password'),
                'phoneEntered' => $this->getRequest()->getPost('phoneEntered')
            ];
        } catch (\Exception $e) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }
        if (!$credentials || $this->getRequest()->getMethod() !== 'POST' || !$this->getRequest()->isXmlHttpRequest()) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }

        try {
            
            if ($this->customerSession->isLoggedIn()) {

                $customerInstance = $this->customerSession->getCustomer();

                $customerData = array (
                    'email' => $customerInstance->getEmail(),
                    'fullname' => $customerInstance->getName(),
                    'firstname' => '',
                    'lastname' => '',
                    'ID' => $customerInstance->getID(),
                );

                $response = [
                    'errors' => false,
                    'customer' => $customerData,
                    'message' => __('User login.')
                ];
            } else {

                $response = [
                    'errors' => true,
                    'message' => __('User not login.')
                ];
            }

        } catch (\Exception $e) {
            $response = [
                'errors' => true,
                'message' => __('Invalid login or password. ').$e->getMessage()
            ];
        }

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($response);
    }
}