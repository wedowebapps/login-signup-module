<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Lordhair\LoginSignup\Controller\Ajax;

use Lordhair\LoginSignup\Model\MailInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\DataObject;

/**
 * Login controller
 *
 * @method \Magento\Framework\App\RequestInterface getRequest()
 * @method \Magento\Framework\App\Response\Http getResponse()
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Register extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Magento\Framework\Json\Helper\Data $helper
     */
    protected $helper;

    protected $basehelper;

    protected $geohelper;

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
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Json\Helper\Data $helper
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Json\Helper\Data $helper,
        \Infortis\Base\Helper\Data $basehelper,
        \Magecomp\Geocurrencystore\Helper\Data $geohelper,
        \Lordhair\LoginSignup\Model\Customer $customerModel,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        MailInterface $mail,
        \Lordhair\Customerservices\Helper\Data $customerService,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->customerSession = $customerSession;
        $this->helper = $helper;
        $this->basehelper = $basehelper;
        $this->geohelper = $geohelper;
        $this->customerModel = $customerModel;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->mail = $mail;
        $this->customerService = $customerService;
        $this->logger = $logger;
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

        if ($this->customerModel->userExists($this->getRequest()->getPost('regEmail'))) {
            $response = [
                'errors' => true,
                'errotTyep' => 'emialExist',
                'message' => __('A user already exists with this email id')
            ];
        } else if ($this->getRequest()->getPost('regMobileNo') && $this->customerModel->getByPhoneNumberBoolean($this->getRequest()->getPost('regMobileNo'))){
            $response = [
                'errors' => true,
                'errotTyep' => 'phoneExist',
                'message' => __('A user already exists with this phone')
            ];
        } else {
            /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
            $resultRaw = $this->resultRawFactory->create();
            $createOtpCode = rand(1000,9999);

            try {
                $ip_cur = $_SERVER['REMOTE_ADDR'];
                if(empty($this->basehelper->getSelfSession('ip')) || $this->basehelper->getSelfSession('ip') != $ip_cur){
                    $current_county = $this->geohelper->getCountryByIp($ip_cur);
                    $this->basehelper->setSelfSession('ip',$ip_cur);
                    $this->basehelper->setSelfSession('currentcounty',$current_county);
                }else{
                    $current_county = $this->basehelper->getSelfSession('currentcounty');
                }

                if('VN' == $current_county){
                    $response = [
                        'errors'    => true,
                        'errotTyep' => 'something',
                        'message'   => __('Something went wrong please try again after sometime.')
                    ];
                }

                $userData = [
                    'firstname' => $this->getRequest()->getPost('regFirstName'),
                    'lastname' => $this->getRequest()->getPost('regLastName'),
                    'email' => $this->getRequest()->getPost('regEmail'),
                    'regCountryISOCode' => $this->getRequest()->getPost('regCountryISOCode'),
                    'regCountryCode' => $this->getRequest()->getPost('regCountryCode'),
                    'gender' => $this->getRequest()->getPost('regGender'),
                    'telephone' => $this->getRequest()->getPost('regMobileNo'),
                    'password' => $this->getRequest()->getPost('regPasssword'),
                    'password_confirmation' => $this->getRequest()->getPost('regConfPasssword'),
                    'is_subscribed' => $this->getRequest()->getPost('regSignNewsletter'),
                    'mobileconfirmation' => $this->getRequest()->getPost('mobileconfirmation'),
                    'name' => $this->getRequest()->getPost('regFirstName').' '.$this->getRequest()->getPost('regLastName'),
                    'OTP' => $createOtpCode
                ];
            } catch (\Exception $e) {
                return $resultRaw->setHttpResponseCode($httpBadRequestCode);
            }

            if (!$userData || $this->getRequest()->getMethod() !== 'POST' || !$this->getRequest()->isXmlHttpRequest()) {
                return $resultRaw->setHttpResponseCode($httpBadRequestCode);
            }

            try {

                $isUserRegistered= $this->customerModel->createUserFromPopup($userData);
                $userData['staff_email'] = $isUserRegistered[1];
                if (!isset($isUserRegistered[0])) {
                    $response = [
                        'errors'    => true,
                        'errotTyep' => 'something',
                        'message'   => __('Something went wrong please try again after sometime.')
                    ];
                }
                //get customer service of Whatsapp
                try{
                    $customerService = $this->customerService->getCustomerServiceByStaffEmail($userData['staff_email']);
                }catch (\Exception $e){
                    $this->logger->error($e->getMessage());
                    $customerService = [];
                }
                $userData = array_merge($userData, [
                    'customer_service_name' => $customerService['name']?? "",
                    'customer_service_email' => $customerService['email']?? "",
                    'customer_service_tel' => $customerService['tel']?? "",
                ]);

                //send OTP Vaerification email to customer
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
                    'message' => __('Something went wrong please try again after sometime.').$e->getMessage()
                ];
            }
        }

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($response);
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
