<?php

namespace Lordhair\LoginSignup\Controller\Ajax;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Framework\Exception\EmailNotConfirmedException;
use Magento\Framework\Exception\InvalidEmailOrPasswordException;
use Magento\Framework\Exception\LocalizedException;
use Lordhair\LoginSignup\Model\Customer;
use Lordhair\LoginSignup\Model\MailInterface;
use Lordhair\LoginSignup\Setup\InstallData;
use Magento\Framework\DataObject;

/**
 * emailVerifyLogin controller
 *
 * @method \Magento\Framework\App\RequestInterface getRequest()
 * @method \Magento\Framework\App\Response\Http getResponse()
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EmailVerifyLogin extends \Magento\Framework\App\Action\Action
{

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
     * @var \Lordhair\LoginSignup\Model\Customer $customerModel
     */
    protected $customerModel;

    /**
    * @var MailInterface
    */
    private $mail;

    /**
     * @var \Magento\Customer\Model\SessionFactory
     */
    protected $customerSession;

    /**
     * @var \Lordhair\Customerservices\Helper\Data
     */
    protected $customerService;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Initialize Login controller
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $session
     * @param \Magento\Framework\Json\Helper\Data $helper
     * @param AccountManagementInterface $customerAccountManagement
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $session,
        \Magento\Framework\Json\Helper\Data $helper,
        AccountManagementInterface $customerAccountManagement,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        Customer $customerModel,
        MailInterface $mail,
        \Lordhair\Customerservices\Helper\Data $customerService,
        \Psr\Log\LoggerInterface $logger
    ) {

        $this->customerSession = $session;
        $this->helper = $helper;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->_customer = $customerFactory;
        $this->customerModel = $customerModel;
        $this->mail = $mail;
        $this->customerService = $customerService;
        $this->logger = $logger;
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
                'otp' => $this->getRequest()->getPost('otp'),
                'username' => $this->getRequest()->getPost('email'),
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

            if ($credentials['phoneEntered'] && $credentials['phoneEntered'] == 'true') {
                $getCustomer = $this->customerModel->getByPhoneNumber($credentials['username']);
            } else {
                $getCustomer = $this->customerModel->userByEmail($credentials['username']);
            }

            if (!$getCustomer) {
                throw new EmailNotConfirmedException(__('Account not exist with entered details.'));
            }

            $getCustomer = $getCustomer->getData();

            if (isset($getCustomer[0])) {
                $getCustomer = $getCustomer[0];
            }

            $credentials['otp'] = implode('', $credentials['otp']);

            $customerObj = $this->getCustmer($getCustomer['entity_id']);

            $customerDataModel = $customerObj->getDataModel();

            $getSavedOTP = $customerDataModel->getCustomAttribute(InstallData::OTP_VERIFICATION);

            if (!$getSavedOTP) {

                throw new LocalizedException(__('Something went wrong please try again!'));
            }

            if ($getSavedOTP->getValue() != $credentials['otp']) {

                throw new LocalizedException(__('Invalid OTP please enter valid OTP!'));
            }

            //get customer service of Whatsapp
            try{
                $customerService = $this->customerService->getCustomerService($customerDataModel->getEmail());
            }catch (\Exception $e){
                $this->logger->error($e->getMessage());
                $customerService = [];
            }

            $response['customer'] = array(
                'email' => $customerDataModel->getEmail(),
                'fullname' => $customerDataModel->getFirstName().' '.$customerDataModel->getLastName(),
                'firstname' => $customerDataModel->getFirstName(),
                'lastname' => $customerDataModel->getLastName(),
                'password' => 'Which you have entered while registration',
                'name' => $customerDataModel->getFirstName().' '.$customerDataModel->getLastName(),
                'ID' => $customerDataModel->getID(),
                'customer_service_name' => $customerService['name']?? "",
                'customer_service_email' => $customerService['email']?? "",
                'customer_service_tel' => $customerService['tel']?? "",
            );

            $customerObj->setConfirmation(null);
            $customerObj->save();

            $this->customerModel->dispatchWithoutPassEvents($customerObj);
            $this->customerSession->setCustomerAsLoggedIn($customerObj);

            //send welcome email to customer
            $this->sendEmail($response['customer']);

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
