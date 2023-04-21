<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Lordhair\LoginSignup\Controller\Ajax;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Magento\Customer\Model\Session as customerSession;
use Lordhair\LoginSignup\Model\Customer as customerModel;
use Magento\Customer\Model\CustomerFactory;
use Lordhair\LoginSignup\Setup\InstallData;

/**
 * SendOtp controller
 *
 * @method \Magento\Framework\App\RequestInterface getRequest()
 * @method \Magento\Framework\App\Response\Http getResponse()
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OtpVerify extends \Magento\Framework\App\Action\Action
{

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
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \Magento\Customer\Model\Session $customerSession
     */
    protected $customerSession;

    /**
     * @var \Lordhair\LoginSignup\Model\Customer $customerModel
     */
    protected $customerModel;

    /**
     * @var \Magento\Customer\Model\CustomerFactory $customerFactory
     */
    protected $customerFactory;

    /**
     * Initialize SendOtp controller
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Json\Helper\Data $helper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        customerSession $customerSession,
        customerModel $customerModel,
        CustomerFactory $customerFactory
    ) {
       
        $this->helper               = $helper;
        $this->resultJsonFactory    = $resultJsonFactory;
        $this->resultRawFactory     = $resultRawFactory;
        $this->storeManager         = $storeManager;
        $this->logger               = $logger;
        $this->customerSession      = $customerSession;
        $this->customerModel        = $customerModel;
        $this->_customer            = $customerFactory;
        parent::__construct($context);
    }

    public function execute() {
        
        $credentials = null;
        $httpBadRequestCode = 400;

        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $resultRaw = $this->resultRawFactory->create();
        try {
            $credentials = [
                'telephone' => $this->getRequest()->getPost('telephone'),
                'email' => $this->getRequest()->getPost('email'),
                'countryCode' => $this->getRequest()->getPost('countryCode'),
                'otp' => $this->getRequest()->getPost('otp')
            ];
        } catch (\Exception $e) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }
        if (!$credentials || $this->getRequest()->getMethod() !== 'POST' || !$this->getRequest()->isXmlHttpRequest()) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }

        $response = [
            'errors' => false,
            'message' => __('OTP verify successfully.')
        ];

        try {
            
            if ($credentials['email']) {

                $getCustomer = $this->customerModel->userByEmail($credentials['email']);

                if (!$getCustomer) {
                    throw new LocalizedException(__('Account not exist with entered details.'));
                }

                $getCustomer = $getCustomer->getData();
                $customerObj = $this->getCustmer($getCustomer['entity_id']);
                $customerDataModel = $customerObj->getDataModel();

                $getSavedOTP = $customerDataModel->getCustomAttribute(InstallData::OTP_VERIFICATION);

                if (!$getSavedOTP) {
                    throw new LocalizedException(__('Something went wrong please try again!'));
                }
                
                $credentials['otp'] = implode('', $credentials['otp']);

                if ($getSavedOTP->getValue() == $credentials['otp']) {
                    $attrArray = array(
                        'attr' => InstallData::OTP_VERIFICATION,
                        'value' => ''
                    );
                    $this->customerModel->saveCustomerAttr($getCustomer['entity_id'], $attrArray);
                    $attrArray = array(
                        'attr' => InstallData::MOBILE_CONFIRMATION,
                        'value' => 1
                    );
                    $this->customerModel->saveCustomerAttr($getCustomer['entity_id'], $attrArray);
                    $attrArray = array(
                        'attr' => InstallData::PHONE_NUMBER,
                        'value' => $credentials['telephone']
                    );
                    $this->customerModel->saveCustomerAttr($getCustomer['entity_id'], $attrArray);
                    $attrArray = array(
                        'attr' => InstallData::COUNTRY_CODE,
                        'value' => $credentials['countryCode']
                    );
                    $this->customerModel->saveCustomerAttr($getCustomer['entity_id'], $attrArray);
                    $attrArray = array(
                        'attr' => InstallData::PHONE_NUMBER_CODE,
                        'value' => preg_replace('/[^a-zA-Z0-9_ -]/s','',$credentials['countryCode']).$credentials['telephone']
                    );
                    $this->customerModel->saveCustomerAttr($getCustomer['entity_id'], $attrArray);
                } else{
                    throw new LocalizedException(__('Invalid OTP please enter valid OTP!'));
                }

            } else {
                $VerMobileData = $this->customerSession->getVerMobileData();
                if (!isset($VerMobileData)) {
                    $response = [
                        'errors' => true,
                        'message' => __('Please request OTP first')
                    ];
                }
                if (!in_array($credentials['telephone'], $VerMobileData)) {
                    
                    $response = [
                        'errors' => true,
                        'message' => __('Please request OTP first')
                    ];
                }
                $credentials['otp'] = implode('', $credentials['otp']);
                if (!in_array($credentials['otp'], $VerMobileData)) {
                    $response = [
                        'errors' => true,
                        'message' => __('Invalid OTP please enter valid OTP')
                    ];
                }   
            }

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
                'message' => $e->getMessage()
            ];
        }

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($response);
    }

    public function getCustmer($getCustomerId) {
        $customer = $this->_customer->create()->load($getCustomerId);
        return $customer;
    }
}