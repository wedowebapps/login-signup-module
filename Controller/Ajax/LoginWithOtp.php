<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Lordhair\LoginSignup\Controller\Ajax;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Framework\Exception\EmailNotConfirmedException;
use Magento\Framework\Exception\InvalidEmailOrPasswordException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Lordhair\LoginSignup\Model\Customer;
use Lordhair\LoginSignup\Setup\InstallData;

/**
 * Login controller
 *
 * @method \Magento\Framework\App\RequestInterface getRequest()
 * @method \Magento\Framework\App\Response\Http getResponse()
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class LoginWithOtp extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Session\Generic
     */
    protected $session;

    /**
     * @var AccountManagementInterface
     */
    protected $customerAccountManagement;

    /**
     * @var \Magento\Framework\Json\Helper\Data $helper
     */
    protected $helper;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Lordhair\LoginSignup\Model\Customer $customerModel
     */
    protected $customerModel;

    /**
     * Initialize Login controller
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\SessionFactory $sessionFactory
     * @param \Magento\Framework\Json\Helper\Data $helper
     * @param AccountManagementInterface $customerAccountManagement
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\SessionFactory $sessionFactory,
        \Magento\Framework\Json\Helper\Data $helper,
        AccountManagementInterface $customerAccountManagement,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        StoreManagerInterface $storeManager,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        Customer $customerModel
    ) {
       
        $this->customerSession = $sessionFactory;
        $this->helper = $helper;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->storeManager = $storeManager;
        $this->_customer = $customerFactory;
        $this->customerModel = $customerModel;
        parent::__construct($context);
    }

    /**
     * @deprecated
     * @param ScopeConfigInterface $value
     * @return void
     */
    public function setScopeConfig($value)
    {
        $this->scopeConfig = $value;
    }

    /**
     * Login registered users and initiate a session.
     *
     * Expects a POST. ex for JSON {"username":"user@magento.com", "password":"userpassword"}
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        $credentials = null;
        $httpBadRequestCode = 400;

        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $resultRaw = $this->resultRawFactory->create();
        try {
            $credentials = [
                'otp' => $this->getRequest()->getPost('otp'),
                'username' => $this->getRequest()->getPost('username'),
                'phoneEntered' => $this->getRequest()->getPost('phoneEntered')
            ];
        } catch (\Exception $e) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }
        if (!$credentials || $this->getRequest()->getMethod() !== 'POST' || !$this->getRequest()->isXmlHttpRequest()) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }

        $response = [
            'errors' => false,
            'customer' => '',
            'message' => __('Login successful.')
        ];

        try {
            
            if (!$credentials['phoneEntered']) {
                throw new Exception(__('Something went wrong please try again!'));
            }

            $getCustomer = $this->customerModel->getByPhoneNumber($credentials['username']);
            
            if ($getCustomer->getSize() < 1) {
                throw new EmailNotConfirmedException(__('Account not exist with entered details.'));
            }

            $getCustomer = $getCustomer->getData();

            $credentials['otp'] = implode('', $credentials['otp']);

            $customerObj = $this->getCustmer($getCustomer);
            
            if ($this->customerAccountManagement->getConfirmationStatus($customerObj->getID()) == 'account_confirmation_required') {
                throw new EmailNotConfirmedException(__("This account isn't confirmed. Verify and try again."));
            }

            $customerDataModel = $customerObj->getDataModel();
            
            $getSavedOTP = $customerDataModel->getCustomAttribute(InstallData::OTP_VERIFICATION);

            if (!$getSavedOTP) {
                
                throw new LocalizedException(__('Something went wrong please try again!'));
            }

            if ($getSavedOTP->getValue() != $credentials['otp']) {

                throw new LocalizedException(__('Invalid OTP please enter valid OTP!'));
            }

            $response['customer'] = array(
                'email' => $customerDataModel->getEmail(),
                'fullname' => $customerDataModel->getFirstName().' '.$customerDataModel->getLastName(),
                'firstname' => $customerDataModel->getFirstName(),
                'lastname' => $customerDataModel->getLastName(),
                'ID' => $customerDataModel->getID(),
            );
            
            //$this->customerModel->dispatchWithoutPassEvents($customerObj);
            $sessionManager = $this->customerSession->create();
            $sessionManager->setCustomerAsLoggedIn($customerObj);

        } catch (EmailNotConfirmedException $e) {
            $response = [
                'errors' => true,
                'message' => $e->getMessage()
            ];
        } catch (InvalidEmailOrPasswordException $e) {
            $response = [
                'errors' => true,
                'message' => $e->getMessage()
            ];
        } catch (LocalizedException $e) {
            $response = [
                'errors' => true,
                'message' => $e->getMessage()
            ];
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

    public function getCustmer($getCustomerData) {

        $customer = $this->_customer->create()->load($getCustomerData[0]['entity_id']);
        return $customer;
    }
}