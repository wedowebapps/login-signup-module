<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Lordhair\LoginSignup\Controller\Ajax;

use Magento\Framework\Exception\LocalizedException;
use Magento\Customer\Model\Session as CustomerSession;
use Lordhair\LoginSignup\Setup\InstallData;
use Lordhair\LoginSignup\Setup\UpgradeData;

/**
 * Login controller
 *
 * @method \Magento\Framework\App\RequestInterface getRequest()
 * @method \Magento\Framework\App\Response\Http getResponse()
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CheckLoggedIn extends \Magento\Framework\App\Action\Action
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
     * @var CustomerSession
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
        \Magento\Framework\App\Action\Context $context,
        \Lordhair\LoginSignup\Helper\Data $helper,
        CustomerSession $customerSession,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
    ) {
       
        $this->helper = $helper;
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
                $customerDataModel = $customerInstance->getDataModel();
                $telephone = '';
                $countryCode = '';
                $regCountryISOCode = '';
                $mobileConfirmation = false;
                if ($customerDataModel->getCustomAttribute(InstallData::PHONE_NUMBER)) {
                    $telephone = $customerDataModel->getCustomAttribute(InstallData::PHONE_NUMBER)->getValue();
                }
                if ($customerDataModel->getCustomAttribute(InstallData::COUNTRY_CODE)) {
                    $countryCode = $customerDataModel->getCustomAttribute(InstallData::COUNTRY_CODE)->getValue();
                }
                if ($customerDataModel->getCustomAttribute(InstallData::MOBILE_CONFIRMATION)) {
                    $mobileConfirmation = $customerDataModel->getCustomAttribute(InstallData::MOBILE_CONFIRMATION)->getValue();
                }
                if ($customerDataModel->getCustomAttribute(UpgradeData::COUNTRY_ISO_CODE)) {
                    $regCountryISOCode = $customerDataModel->getCustomAttribute(UpgradeData::COUNTRY_ISO_CODE)->getValue();
                }

                $customerData = array (
                    'email' => $customerInstance->getEmail(),
                    'fullname' => $customerInstance->getName(),
                    'firstname' => '',
                    'lastname' => '',
                    'telephone' => $telephone,
                    'regCountryISOCode' => $regCountryISOCode,
                    'countryCode' => $countryCode,
                    'mobileConfirmation' => $mobileConfirmation,
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

        $getStoreConfigs = array(
            'desktopImage' => $this->helper->getDesktopImage(),
            'mobileImage' => $this->helper->getMobileImage()
        );
        $response['storeConfigs'] = $getStoreConfigs;

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($response);
    }
}