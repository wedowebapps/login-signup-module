<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Lordhair\LoginSignup\Controller\Ajax;

use Lordhair\LoginSignup\Model\MailInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\DataObject;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\SessionFactory as customerSession;

/**
 * Login controller
 *
 * @method \Magento\Framework\App\RequestInterface getRequest()
 * @method \Magento\Framework\App\Response\Http getResponse()
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SignupWithFb extends \Magento\Framework\App\Action\Action
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
     * Initialize Login controller
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\SessionFactory $customerSession
     * @param \Magento\Framework\Json\Helper\Data $helper
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        customerSession $customerSession,
        \Magento\Framework\Json\Helper\Data $helper,
        \Lordhair\LoginSignup\Model\Customer $customerModel,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        MailInterface $mail,
        CustomerFactory $customerFactory
    ) {
        
        $this->customerSession = $customerSession;
        $this->helper = $helper;
        $this->customerModel = $customerModel;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->mail = $mail;
        $this->_customer = $customerFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $userData = null;
        $httpBadRequestCode = 400;
        
        $response = [
            'errors' => false,
            'message' => __('Login successful.')
        ];

        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $resultRaw = $this->resultRawFactory->create();
        $createOtpCode = rand(1000,9999);

        try {
            $userData = [
                'firstname' => $this->getRequest()->getPost('regFirstName'),
                'lastname' => $this->getRequest()->getPost('regLastName'),
                'email' => $this->getRequest()->getPost('regEmail'),
                'gender' => $this->getRequest()->getPost('regGender'),
                'name' => $this->getRequest()->getPost('regFirstName').' '.$this->getRequest()->getPost('regLastName')
            ];
        } catch (\Exception $e) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }

        if (!$userData || $this->getRequest()->getMethod() !== 'POST' || !$this->getRequest()->isXmlHttpRequest()) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }
        
        try {
            
            $isEmailSend = false;
            if ($this->customerModel->userExists($userData['email'])) {

                $getCustomer = $this->customerModel->userByEmail($userData['email']);
                $customerObj = $this->getCustmer($getCustomer['entity_id']);

            } else {
                
                $isUserRegistered = $this->customerModel->createUser($userData);
                if (!$isUserRegistered) {
                    $response = [
                        'errors'    => true,
                        'errotTyep' => 'something',
                        'message'   => __('Something went wrong please try again after sometime.')
                    ];
                }
                $customerObj = $this->getCustmer($isUserRegistered);
                $isEmailSend = true;
            }

            $customerDataModel = $customerObj->getDataModel();
            
            $response['customer'] = array(
                'email' => $customerDataModel->getEmail(),
                'fullname' => $customerDataModel->getFirstName().' '.$customerDataModel->getLastName(),
                'firstname' => $customerDataModel->getFirstName(),
                'lastname' => $customerDataModel->getLastName(),
                'password' => '',
                'name' => $customerDataModel->getFirstName().' '.$customerDataModel->getLastName(),
                'ID' => $customerDataModel->getID(),
            );
            
            $customerObj->setConfirmation(null);
            $customerObj->save();

            //$this->customerModel->dispatchWithoutPassEvents($customerObj);
            $sessionManager = $this->customerSession->create();
            $sessionManager->setCustomerAsLoggedIn($customerObj);

            if ($isEmailSend) {
                //send welcome email to customer
                $this->sendEmail($userData);
            }

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
                'message' => __('Something went wrong please try again after sometime.')
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

    private function sendEmail($post)
    {
        $post['tempId'] = 16;
        $this->mail->send(
            $post['email'],
            ['customer' => new DataObject($post), 'ip' => $_SERVER["REMOTE_ADDR"]]
        );
    }
}