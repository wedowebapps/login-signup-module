<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Lordhair\LoginSignup\Controller\Ajax;

class Logout extends \Magento\Framework\App\Action\Action
{
    protected $session;

    protected $resultJsonFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    public function execute()
    {
        try {
            $lastCustomerId = $this->customerSession->getId();
            $this->customerSession->logout()
                ->setBeforeAuthUrl($this->_redirect->getRefererUrl())
                ->setLastCustomerId($lastCustomerId);

            $resultJson = $this->resultJsonFactory->create();
            $response = [
                'errors' => false,
                'message' => __('Logout Successful')
            ];
        } catch (\Exception $e) {
            $response = [
                'errors' => true,
                'message' => $e->getMessage()
            ];
        }
        return $resultJson->setData($response);
    }
}