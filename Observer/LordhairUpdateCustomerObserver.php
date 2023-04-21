<?php

namespace Lordhair\Loginsignup\Observer;

use Magento\Framework\Event\ObserverInterface;
use Lordhair\LoginSignup\Model\Customer as lordhairCustomerModel;
use Magento\Customer\Model\CustomerFactory;
use Lordhair\LoginSignup\Setup\InstallData;
use Lordhair\LoginSignup\Setup\UpgradeData;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;

class LordhairUpdateCustomerObserver implements ObserverInterface {

    protected $customerModel;
    protected $customerFactory;
    private $customerCollectionFactory;

    public function __construct(
        \Magento\Framework\Session\SessionManagerInterface $session,
        lordhairCustomerModel $customerModel,
        CustomerFactory $customerFactory,
        CustomerCollectionFactory $customerCollectionFactory
    ){
        $this->customerModel = $customerModel;
        $this->_customer = $customerFactory;
        $this->customerCollectionFactory = $customerCollectionFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer) { 
        try {
            $customer = $observer->getEvent();
            $getCustomer = $this->customerModel->userByEmail($customer->getData('email'));
            $customerObj = $this->getCustmer($getCustomer['entity_id']);
            $customerDataModel = $customerObj->getDataModel();
            $needToSave = 1;
            if (isset($_POST['loginsignup_telephone']) && isset($_POST['loginsignup_countrycode'])) {
                $collection = $this->customerCollectionFactory->create()->addAttributeToFilter(InstallData::PHONE_NUMBER, $_POST['loginsignup_telephone']);
                if ($collection->getSize() > 0) {
                    $getCustomrsList = $collection->getData();
                    $customerData = $customerObj->getData();
                    $needToSave = 1;
                    if ($getCustomrsList[0]['entity_id'] == $customerData['entity_id']) {
                        $needToSave = 1;
                    } else {
                        $needToSave = 0;
                    }
                }

                if ($needToSave) {
                    $attrArray = array(
                        'attr' => InstallData::PHONE_NUMBER,
                        'value' => $_POST['loginsignup_telephone']
                    );
                    $this->customerModel->saveCustomerAttr($customerDataModel->getID(), $attrArray);
                    $attrArray = array(
                        'attr' => InstallData::COUNTRY_CODE,
                        'value' => $_POST['loginsignup_countrycode']
                    );
                    $this->customerModel->saveCustomerAttr($customerDataModel->getID(), $attrArray);
                    
                    $regCountryCode = preg_replace('/[^a-zA-Z0-9_ -]/s','',$_POST['loginsignup_countrycode']);
                    $attrArray = array(
                        'attr' => InstallData::PHONE_NUMBER_CODE,
                        'value' => $regCountryCode.$_POST['loginsignup_telephone']
                    );
                    $this->customerModel->saveCustomerAttr($customer->getId(), $attrArray);

                    if (isset($_POST['loginsignup_countryisocode'])) {
                        $attrArray = array(
                            'attr' => UpgradeData::COUNTRY_ISO_CODE,
                            'value' => $_POST['loginsignup_countryisocode']
                        );
                        $this->customerModel->saveCustomerAttr($customer->getID(), $attrArray);
                    }

                    if (isset($_POST['loginsignup_mobileconfirmation'])) {
                        $attrArray = array(
                            'attr' => InstallData::MOBILE_CONFIRMATION,
                            'value' => $_POST['loginsignup_mobileconfirmation']
                        );
                        $this->customerModel->saveCustomerAttr($customer->getID(), $attrArray);
                    }
                }
            }
        } catch (\Exception $e) {
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/LordUpdateCusObserver.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info($e->getMessage());
        }
    }

    public function getCustmer($getCustomerId) {
        $customer = $this->_customer->create()->load($getCustomerId);
        return $customer;
    }
}