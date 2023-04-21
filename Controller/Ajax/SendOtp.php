<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Lordhair\LoginSignup\Controller\Ajax;

use Magento\Framework\Exception\InvalidEmailOrPasswordException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Twilio\Rest\ClientFactory;
use Psr\Log\LoggerInterface;
use Lordhair\LoginSignup\Model\Customer; 
use Lordhair\LoginSignup\Setup\InstallData;
use Magento\Customer\Model\Session as customerSession;
use Magento\Customer\Model\ResourceModel\CustomerFactory;

/**
 * SendOtp controller
 *
 * @method \Magento\Framework\App\RequestInterface getRequest()
 * @method \Magento\Framework\App\Response\Http getResponse()
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SendOtp extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Lordhair\LoginSignup\Helper\Data $helper
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
     * @var \Lordhair\LoginSignup\Model\Customer $customerModel
     */
    protected $customerModel;

    /**
     * @var \Magento\Customer\Model\Customer $customer
     */
    protected $customer;

    /**
     * @var \Magento\Customer\Model\ResourceModel\CustomerFactory $customerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Customer\Model\Session $customerSession
     */
    protected $customerSession;

    /**
     * Initialize SendOtp controller
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Lordhair\LoginSignup\Helper\Data $helper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        ClientFactory $clientFactory,
        Customer $customerModel,
        \Magento\Customer\Model\Customer $customer,
        CustomerFactory $customerFactory,
        customerSession $customerSession
    ) {
       
        $this->helper               = $helper;
        $this->resultJsonFactory    = $resultJsonFactory;
        $this->resultRawFactory     = $resultRawFactory;
        $this->storeManager         = $storeManager;
        $this->logger               = $logger;
        $this->clientFactory        = $clientFactory;
        $this->customerModel        = $customerModel;
        $this->customer             = $customer;
        $this->customerFactory      = $customerFactory->create();
        $this->customerSession      = $customerSession;
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
                'registration' => $this->getRequest()->getPost('registration'),
                'countryCode' => $this->getRequest()->getPost('countryCode'),
                'email' => $this->getRequest()->getPost('email'),
            ];
        } catch (\Exception $e) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }
        if (!$credentials || $this->getRequest()->getMethod() !== 'POST' || !$this->getRequest()->isXmlHttpRequest()) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }

        $response = [
            'errors' => false,
            'message' => __('OTP sent successfully.')
        ];

        try {

            $TWILIO_SID = $this->helper->getTwilioSid();
            $TWILIO_TOKEN = $this->helper->getTwilioToken();
            $TWILIO_NUMBER = $this->helper->getTwilioNumber();

            $createOtpCode = rand(1000,9999);
            
            $client = $this->clientFactory->create([
                'username' => $TWILIO_SID,
                'password' => $TWILIO_TOKEN,
            ]);

            if ($credentials['registration'] && $credentials['countryCode']) {

                $tempMobileArray = array (
                    'mobile' => $credentials['telephone'],
                    'opt' => $createOtpCode
                );

                $this->customerSession->setVerMobileData($tempMobileArray);

                $credentials['telephone'] = $credentials['countryCode'].$credentials['telephone'];

            } else if ($credentials['countryCode']){
                
                $getCustomer = $this->customerModel->userByEmail($credentials['email']);

                if (!$getCustomer) {
                    throw new LocalizedException(__('Account not exist with entered details.'));
                }
                
                $getCustomerData = array();

                $getCustomerData[] = $getCustomer->getData();

                $credentials['telephone'] = $credentials['countryCode'].$credentials['telephone'];

                $this->saveCustomerOtp($getCustomerData, $createOtpCode);

            } else {

                $getCustomer = $this->customerModel->getByPhoneNumber($credentials['telephone']);

                if ($getCustomer->getSize() < 1) {
                    throw new \Exception(__('Account not exist with entered details.'));
                }

                $getCustomerData = $getCustomer->getData();
                
                $getCountryCode = $this->getCustmer($getCustomerData)->getCustomAttribute(InstallData::COUNTRY_CODE);
                $getTelephone = $this->getCustmer($getCustomerData)->getCustomAttribute(InstallData::PHONE_NUMBER);

                if ($getCountryCode && $getCountryCode->getValue() && $getTelephone->getValue()) {
                    
                    $credentials['telephone'] = $getCountryCode->getValue().$getTelephone->getValue();
                }

                $this->saveCustomerOtp($getCustomerData, $createOtpCode);
            }

            $params = [
                'from' => $TWILIO_NUMBER,
                'body' => $this->getBody($createOtpCode),
            ];

            $client->messages->create($credentials['telephone'], $params);

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

    public function getBody($otpCode) {

        $result  = "Your Lordhair One Time Password(OTP) for verification is: ";
        $result .= $otpCode;
        $result .= ". Please don’t share with anyone.";
        return $result;
    }

    public function getCustmer ($getCustomerData) {

        $customer = $this->customer->load($getCustomerData[0]['entity_id']);
        $customer = $customer->getDataModel();
        return $customer;
    }

    public function saveCustomerOtp ($getCustomerData, $otpCode) {
        
        $customer = $this->customer->load($getCustomerData[0]['entity_id']);
        $customerData = $customer->getDataModel();
        $customerData->setCustomAttribute(InstallData::OTP_VERIFICATION, $otpCode);
        $customer->updateData($customerData);
        $customerResource = $this->customerFactory;
        $customerResource->saveAttribute($customer, InstallData::OTP_VERIFICATION);
        return true;
    }

    public function getWebsiteId() {
        return $this->storeManager->getWebsite()->getWebsiteId();
    }
}