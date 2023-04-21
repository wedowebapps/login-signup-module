<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Lordhair\LoginSignup\Controller\Ajax;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Framework\Exception\EmailNotConfirmedException;
use Magento\Framework\Exception\InvalidEmailOrPasswordException;
use Magento\Framework\App\ObjectManager;
use Magento\Customer\Model\Account\Redirect as AccountRedirect;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Lordhair\LoginSignup\Model\Customer;

/**
 * Login controller
 *
 * @method \Magento\Framework\App\RequestInterface getRequest()
 * @method \Magento\Framework\App\Response\Http getResponse()
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Login extends \Magento\Framework\App\Action\Action
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
     * @var AccountRedirect
     */
    protected $accountRedirect;

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
     * @var \Magento\Customer\Model\Session $customerModelSession
     */
    protected $customerModelSession;

    /**
     * Initialize Login controller
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\SessionFactory $SessionFactory
     * @param \Magento\Framework\Json\Helper\Data $helper
     * @param AccountManagementInterface $customerAccountManagement
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\SessionFactory $SessionFactory,
        \Magento\Framework\Json\Helper\Data $helper,
        AccountManagementInterface $customerAccountManagement,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        StoreManagerInterface $storeManager,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\Session $customerModelSession,
        Customer $customerModel
    ) {

        $this->customerSession = $SessionFactory;
        $this->helper = $helper;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->storeManager = $storeManager;
        $this->_customer = $customerFactory;
        $this->customerModel = $customerModel;
        $this->customerModelSession = $customerModelSession;
        parent::__construct($context);
    }

    /**
     * Get account redirect.
     * For release backward compatibility.
     *
     * @deprecated
     * @return AccountRedirect
     */
    protected function getAccountRedirect()
    {
        if (!is_object($this->accountRedirect)) {
            $this->accountRedirect = ObjectManager::getInstance()->get(AccountRedirect::class);
        }
        return $this->accountRedirect;
    }

    /**
     * Account redirect setter for unit tests.
     *
     * @deprecated
     * @param AccountRedirect $value
     * @return void
     */
    public function setAccountRedirect($value)
    {
        $this->accountRedirect = $value;
    }

    /**
     * @deprecated
     * @return ScopeConfigInterface
     */
    protected function getScopeConfig()
    {
        if (!is_object($this->scopeConfig)) {
            $this->scopeConfig = ObjectManager::getInstance()->get(ScopeConfigInterface::class);
        }
        return $this->scopeConfig;
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
                'checkEmailExist' => $this->getRequest()->getPost('checkEmailExist'),
                'username' => $this->getRequest()->getPost('username'),
                'password' => $this->getRequest()->getPost('password'),
                'phoneEntered' => $this->getRequest()->getPost('phoneEntered'),
                'customerId' => $this->getRequest()->getPost('customerId')
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

            if ($credentials['checkEmailExist']) {

                if ($credentials['phoneEntered'] == 'true') {

                    $getCustomer = $this->customerModel->getByPhoneNumber($credentials['username']);

                    if ($getCustomer->getSize() < 1) {
                        throw new LocalizedException(__('Account not exist with entered details.'));
                    }

                    $getPhoneCustomer = $getCustomer->getData();
                    $lastCustomerId = $this->customerModelSession->getId();

                    if ($lastCustomerId && $getPhoneCustomer[0]['entity_id'] == $lastCustomerId) {
                        throw new LocalizedException(__('Account have own number.'));
                    }

                    $response = [
                        'errors' => false,
                        'message' => __('A user already exists with this phone')
                    ];

                } else {

                    $credentials['username'] = preg_replace('/\s+/', '', $credentials['username']);
                    $storeId = (int)$this->storeManager->getWebsite()->getId();
                    $isEmailNotExists = $this->customerAccountManagement->isEmailAvailable($credentials['username'], $storeId);
                    if ($isEmailNotExists) {
                        throw new LocalizedException(__('Account not exist with entered details.'));
                    }
                    $response = [
                        'errors' => false,
                        'message' => __('A user already exists with this email')
                    ];
                }

            } else {

                if ($credentials['phoneEntered'] == 'true') {
                    $getCustomer = $this->customerModel->getByPhoneNumber($credentials['username']);
                    if ($getCustomer->getSize() < 1) {
                        throw new LocalizedException(__('Account not exist with entered details.'));
                    }
                    $getPhoneCustomer = $getCustomer->getData();
                    $credentials['username'] = $getPhoneCustomer[0]['email'];
                }

                $customer = $this->customerAccountManagement->authenticate(
                    $credentials['username'],
                    $credentials['password']
                );

                if ($customer->getConfirmation()
                    && \Magento\Customer\Model\AccountConfirmation::isConfirmationRequired($customer->getWebsiteId(), $customer->getId(), $customer->getEmail())) {
                    throw new EmailNotConfirmedException(__("This account isn't confirmed. Verify and try again."));
                }

                $response['customer'] = array(
                    'email' => $customer->getEmail(),
                    'fullname' => $customer->getFirstName().' '.$customer->getLastName(),
                    'firstname' => $customer->getFirstName(),
                    'lastname' => $customer->getLastName(),
                    'ID' => $customer->getID(),
                );

                //$this->customerModel->dispatchEvents($customer, $credentials['password']);
                $customerObj = $this->getCustmer($customer->getID());
                $sessionManager = $this->customerSession->create();
                $sessionManager->setCustomerAsLoggedIn($customerObj);
            }

        } catch (EmailNotConfirmedException $e) {
            $response = [
                'errors' => true,
                'errorType' => 'emailNotConfirm',
                'message' => $e->getMessage()
            ];
        } catch (InvalidEmailOrPasswordException $e) {
            $response = [
                'errors' => true,
                'errorType' => 'invalidEmailPass',
                'message' => $e->getMessage()
            ];
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
                'message' => __('Invalid login or password. ').$e->getMessage()
            ];
        }
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($response);
    }

    public function getCustmer($getCustomerID) {
        $customer = $this->_customer->create()->load($getCustomerID);
        return $customer;
    }
}
