<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Lordhair\LoginSignup\Controller\Ajax;

use Lordhair\LoginSignup\Model\MailInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\DataObject;
use Magento\Customer\Model\ResourceModel\CustomerFactory;
use Lordhair\LoginSignup\Setup\InstallData;

/**
 * Login controller
 *
 * @method \Magento\Framework\App\RequestInterface getRequest()
 * @method \Magento\Framework\App\Response\Http getResponse()
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ResendEmailOtp extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Magento\Framework\Json\Helper\Data $helper
     */
    protected $helper;

    /**
     * @var \Lordhair\LoginSignup\Model\Customer $customerModel
     */
    protected $customerModel;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
    * @var MailInterface
    */
    private $mail;

    /**
     * @var \Magento\Customer\Model\Customer $customer
     */
    protected $customer;

    /**
     * @var \Magento\Customer\Model\ResourceModel\CustomerFactory $customerFactory
     */
    protected $customerFactory;

    /**
     * Initialize Login controller
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Json\Helper\Data $helper
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Json\Helper\Data $helper,
        \Lordhair\LoginSignup\Model\Customer $customerModel,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        MailInterface $mail,
        \Magento\Customer\Model\Customer $customer,
        CustomerFactory $customerFactory
    ) {
        
        $this->customerSession = $customerSession;
        $this->helper = $helper;
        $this->customerModel = $customerModel;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->mail = $mail;
        $this->customer = $customer;
        $this->customerFactory = $customerFactory->create();
        parent::__construct($context);
    }

    public function execute()
    {
        $userData = null;
        $httpBadRequestCode = 400;
        $response = [
            'errors' => false,
            'message' => __('Your Registration is successful now please verify email.')
        ];

         /* @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $resultRaw = $this->resultRawFactory->create();

        try {
            $credentials = [
                'username' => $this->getRequest()->getPost('email'),
                'phoneEntered' => $this->getRequest()->getPost('phoneEntered')
            ];
        } catch (\Exception $e) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }
        if (!$credentials || $this->getRequest()->getMethod() !== 'POST' || !$this->getRequest()->isXmlHttpRequest()) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }

        try {

            $createOtpCode = rand(1000,9999);

            if ($credentials['phoneEntered'] && $credentials['phoneEntered'] == 'true') {
                $getCustomer = $this->customerModel->getByPhoneNumber($credentials['username']);
            } else {
                $getCustomer = $this->customerModel->userByEmail($credentials['username']);
            }

            if (!$getCustomer) {
                throw new LocalizedException(__('Account not exist with entered details.'));
            }

            $getCustomerData = $getCustomer->getData();

            if (isset($getCustomerData[0])) {
                $getCustomerData = $getCustomerData[0];
            }
            
            $this->saveCustomerOtp($getCustomerData, $createOtpCode);

            //send OTP Vaerification email to customer
            $userData = array (
                'email' => $getCustomerData['email'],
                'OTP' => $createOtpCode
            );
            $this->sendEmail($userData);

        } catch (LocalizedException $e) {
            $response = [
                'errors' => true,
                'errotTyep' => 'something',
                'message' => $e->getMessage()
            ];
        } catch (\Exception $e) {
            $response = [
                'errors' => true,
                'errotTyep' => 'something',
                'message' => __($e->getMessage())
            ];
        }
            
        /* @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($response);
    }

    public function saveCustomerOtp ($getCustomerData, $otpCode) {
        $customer = $this->customer->load($getCustomerData['entity_id']);
        $customerData = $customer->getDataModel();
        $customerData->setCustomAttribute(InstallData::OTP_VERIFICATION, $otpCode);
        $customer->updateData($customerData);
        $customerResource = $this->customerFactory;
        $customerResource->saveAttribute($customer, InstallData::OTP_VERIFICATION);
        return true;
    }

    private function sendEmail($post)
    {
        $post['tempId'] = 103;
        $this->mail->send(
            $post['email'],
            ['customer' => new DataObject($post), 'ip' => $_SERVER["REMOTE_ADDR"]]
        );
    }
}