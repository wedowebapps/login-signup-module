<?php
namespace Lordhair\LoginSignup\Model;
use Lordhair\LoginSignup\Helper\Data as HelperData;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\Config\Share as ConfigShare;
use Lordhair\LoginSignup\Setup\InstallData;
use Lordhair\LoginSignup\Setup\UpgradeData;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Customer\Model\Customer as CoreCustomer;
use Magento\Customer\Model\ResourceModel\CustomerFactory;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Newsletter\Model\SubscriberFactory as SubscriberFactory;
use Lordhair\Newsletter\Helper\Data as NewsletterHelper;

class Customer extends \Magento\Framework\Model\AbstractModel
{
    protected $storeManager;
    protected $customerFactory;
    private $helperData;
    private $eventManager;
    protected $_helper;
    private $resourceConnection;
    private $coreCustomer;
    private $customerResourceFactory;
    private $_eavConfig;
    protected $_subscriberFactory;
    protected $_newsletterHelper;
    protected $scopeConfig;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        ResourceConnection $resourceConnection,
        \Lordhair\Customerservices\Helper\Data $helper,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
         \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool,
          \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        CustomerFactory $customerResourceFactory,
        HelperData $helperData,
        ConfigShare $configShare,
        ManagerInterface $eventManager,
        CoreCustomer $coreCustomer,
        EavConfig $eavConfig,
        SubscriberFactory $subscriberFactory,
        NewsletterHelper $newsletterHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->storeManager     = $storeManager;
        $this->customerFactory  = $customerFactory->create();
        $this->helperData = $helperData;
        $this->_helper = $helper;
        $this->resourceConnection = $resourceConnection;
        $this->_cacheTypeList = $cacheTypeList;
        $this->_cacheFrontendPool = $cacheFrontendPool;
         $this->resourceConfig = $resourceConfig;
        $this->configShare = $configShare;
        $this->eventManager = $eventManager;
        $this->coreCustomer = $coreCustomer;
        $this->customerResourceFactory = $customerResourceFactory->create();
        $this->_eavConfig = $eavConfig;
        $this->_subscriberFactory= $subscriberFactory;
        $this->_newsletterHelper = $newsletterHelper;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context,$registry);
    }


    public function getWebsiteId()
    {
        return $this->storeManager->getWebsite()->getWebsiteId();
    }

    public function userExists($email)
    {
        $customer = $this->customerFactory->setWebsiteId($this->getWebsiteId());
        if ($this->customerFactory->loadByEmail($email)->getId()) {
            return true;
        }  else {
            return false;
        }
    }

    public function createUserFromPopup($userData)
    {

        $email = $userData['email'];
        $staff_email = $this->_helper->getStaffEmailByNextForRegister($email);
        $staff_list =  $this->_helper->getAllStaffEmailList();
        $selectsql = "SELECT * FROM assign_email where customer_email='".$email."'";
        $connection  = $this->resourceConnection->getConnection();
        $results_select=$connection->fetchAll($selectsql);
        $postObject = new \Magento\Framework\DataObject();
        if(count($results_select) == 0)
        {
            // insert into assign_email
            $sql_insert = "INSERT INTO assign_email (`staff_email`,`customer_email`,`create_at`) values ('".$staff_email."','".$email."','".date("Y/m/d H:i:s")."')";
            $connection->query($sql_insert);

            if(count($staff_list) > 0){
                $this->resourceConfig->saveConfig('assignemail/lordhair_assignemail_list/email_bingo_register',$staff_email,\Magento\Framework\App\ScopeInterface::SCOPE_DEFAULT,0);
            }

            $types = array('config','layout','block_html','collections','reflection','db_ddl','eav','config_integration','config_integration_api','full_page','translate','config_webservice');
            foreach ($types as $type) {
                $this->_cacheTypeList->cleanType($type);
            }
            foreach ($this->_cacheFrontendPool as $cacheFrontend) {
                $cacheFrontend->getBackend()->clean();
            }
        } else {

            $selectsql = "SELECT staff_email FROM assign_email where customer_email='".$email."'";
            $connection  = $this->resourceConnection->getConnection();
            $results_select=$connection->fetchAll($selectsql);

            $assign_flag = $this->scopeConfig->getValue('assignemail/lordhair_assignemail_list/assign_staff_not_in_list');

            if($assign_flag && !in_array($results_select[0]['staff_email'], $staff_list)){

                $sql_update = "UPDATE assign_email SET staff_email='".$staff_email."', update_at = '".date("Y/m/d H:i:s")."' WHERE customer_email='".$email."'";
                $connection->query($sql_update);
                if(count($staff_list) > 0){
                    $this->resourceConfig->saveConfig('assignemail/lordhair_assignemail_list/email_bingo_register',$staff_email,\Magento\Framework\App\ScopeInterface::SCOPE_DEFAULT,0);
                }
                $types = array('config','layout','block_html','collections','reflection','db_ddl','eav','config_integration','config_integration_api','full_page','translate','config_webservice');
                foreach ($types as $type) {
                    $this->_cacheTypeList->cleanType($type);
                }
                foreach ($this->_cacheFrontendPool as $cacheFrontend) {
                    $cacheFrontend->getBackend()->clean();
                }
                $results_select[0]['staff_email'] = $staff_email;
            }
            $staff_email = $results_select[0]['staff_email'];
        }

        try {
            $customer = $this->customerFactory->setWebsiteId($this->getWebsiteId());
            $customer->setEmail($userData['email']);
            $customer->setFirstname($userData['firstname']);
            $customer->setLastname($userData['lastname']);
            isset($userData['password']) && $customer->setPassword($userData['password']);
            if (isset($userData['gender'])) {
                $gender = $userData['gender'];
                $attribute = $this->_eavConfig->getAttribute('customer', 'gender');
                $options = $attribute->getSource()->getAllOptions();
                foreach ($options as $option) {
                    if ($option['value'] > 0) {
                        if (strcmp(trim($option['label']), trim($userData['gender'])) == 0) {
                            $gender = $option['value'];
                        }
                    }
                }
                $customer->setGender($gender);
            }
            $customer = $customer->save();
            if (isset($userData['telephone'])) {
                $attrArray = array(
                    'attr' => InstallData::PHONE_NUMBER,
                    'value' => $userData['telephone']
                );
                $this->saveCustomerAttr($customer->getID(), $attrArray);
            }
            if (isset($userData['regCountryCode'])) {
                $attrArray = array(
                    'attr' => InstallData::COUNTRY_CODE,
                    'value' => $userData['regCountryCode']
                );
                $this->saveCustomerAttr($customer->getID(), $attrArray);
            }
            if (isset($userData['telephone']) && isset($userData['regCountryCode'])) {
                $regCountryCode = preg_replace('/[^a-zA-Z0-9_ -]/s','',$userData['regCountryCode']);
                $attrArray = array(
                    'attr' => InstallData::PHONE_NUMBER_CODE,
                    'value' => $regCountryCode.$userData['telephone']
                );
                $this->saveCustomerAttr($customer->getID(), $attrArray);
            }
            if (isset($userData['regCountryISOCode'])) {
                $attrArray = array(
                    'attr' => UpgradeData::COUNTRY_ISO_CODE,
                    'value' => $userData['regCountryISOCode']
                );
                $this->saveCustomerAttr($customer->getID(), $attrArray);
            }
            if (isset($userData['OTP'])) {
                $attrArray = array(
                    'attr' => InstallData::OTP_VERIFICATION,
                    'value' => $userData['OTP']
                );
                $this->saveCustomerAttr($customer->getID(), $attrArray);
            }
            if (isset($userData['mobileconfirmation']) && $userData['mobileconfirmation'] == 'true') {
                $attrArray = array(
                    'attr' => InstallData::MOBILE_CONFIRMATION,
                    'value' => 1
                );
                $this->saveCustomerAttr($customer->getID(), $attrArray);
            }
            if (isset($userData['is_subscribed']) && $userData['is_subscribed'] == 'true') {
                $this->_subscriberFactory->create()->subscribe($userData['email']);
                $this->_newsletterHelper->createMailchimpSubscriber($userData['email'],true);
            }
            $data =array();
            $data[0] = $customer->getID();
            $data[1] = $staff_email;

            return $data;

        } catch (\Exception $e) {
            return false;
        }
    }

    public function createUser($userData)
    {

        $email = $userData['email'];

        try {
            $customer = $this->customerFactory->setWebsiteId($this->getWebsiteId());
            $customer->setEmail($userData['email']);
            $customer->setFirstname($userData['firstname']);
            $customer->setLastname($userData['lastname']);
            isset($userData['password']) && $customer->setPassword($userData['password']);
            if (isset($userData['gender'])) {
                $gender = $userData['gender'];
                $attribute = $this->_eavConfig->getAttribute('customer', 'gender');
                $options = $attribute->getSource()->getAllOptions();
                foreach ($options as $option) {
                    if ($option['value'] > 0) {
                        if (strcmp(trim($option['label']), trim($userData['gender'])) == 0) {
                            $gender = $option['value'];
                        }
                    }
                }
                $customer->setGender($gender);
            }
            $customer = $customer->save();
            if (isset($userData['telephone'])) {
                $attrArray = array(
                    'attr' => InstallData::PHONE_NUMBER,
                    'value' => $userData['telephone']
                );
                $this->saveCustomerAttr($customer->getID(), $attrArray);
            }
            if (isset($userData['regCountryCode'])) {
                $attrArray = array(
                    'attr' => InstallData::COUNTRY_CODE,
                    'value' => $userData['regCountryCode']
                );
                $this->saveCustomerAttr($customer->getID(), $attrArray);
            }
            if (isset($userData['telephone']) && isset($userData['regCountryCode'])) {
                $regCountryCode = preg_replace('/[^a-zA-Z0-9_ -]/s','',$userData['regCountryCode']);
                $attrArray = array(
                    'attr' => InstallData::PHONE_NUMBER_CODE,
                    'value' => $regCountryCode.$userData['telephone']
                );
                $this->saveCustomerAttr($customer->getID(), $attrArray);
            }
            if (isset($userData['regCountryISOCode'])) {
                $attrArray = array(
                    'attr' => UpgradeData::COUNTRY_ISO_CODE,
                    'value' => $userData['regCountryISOCode']
                );
                $this->saveCustomerAttr($customer->getID(), $attrArray);
            }
            if (isset($userData['OTP'])) {
                $attrArray = array(
                    'attr' => InstallData::OTP_VERIFICATION,
                    'value' => $userData['OTP']
                );
                $this->saveCustomerAttr($customer->getID(), $attrArray);
            }
            if (isset($userData['mobileconfirmation']) && $userData['mobileconfirmation'] == 'true') {
                $attrArray = array(
                    'attr' => InstallData::MOBILE_CONFIRMATION,
                    'value' => 1
                );
                $this->saveCustomerAttr($customer->getID(), $attrArray);
            }

            return $customer->getID();

        } catch (\Exception $e) {
            return false;
        }
    }

    public function saveCustomerAttr ($ID, $attrArray) {

        $customer = $this->coreCustomer->load($ID);
        $customerData = $customer->getDataModel();
        $customerData->setCustomAttribute($attrArray['attr'], $attrArray['value']);
        $customer->updateData($customerData);
        $customerResource = $this->customerResourceFactory;
        $customerResource->saveAttribute($customer, $attrArray['attr']);
        return true;
    }

    public function getByPhoneNumber(string $phone)
    {
        $phone = preg_replace('/[^a-zA-Z0-9_ -]/s','',$phone);
        $getCustomer = $this->customerFactory->getCollection()
            ->addAttributeToSelect(InstallData::PHONE_NUMBER_CODE)
            ->addAttributeToFilter(InstallData::PHONE_NUMBER_CODE, array('eq' => $phone))
            ->load();
        if ($getCustomer && $getCustomer->getSize() > 0) {
            return $getCustomer;
        }

        return $this->customerFactory->getCollection()
            ->addAttributeToSelect(InstallData::PHONE_NUMBER)
            ->addAttributeToFilter(InstallData::PHONE_NUMBER, array('eq' => $phone))
            ->load();
    }

    public function getByPhoneNumberBoolean(string $phone)
    {
        $phone = preg_replace('/[^a-zA-Z0-9_ -]/s','',$phone);
        $getCustomer = $this->customerFactory->getCollection()
            ->addAttributeToSelect(InstallData::PHONE_NUMBER_CODE)
            ->addAttributeToFilter(InstallData::PHONE_NUMBER_CODE, array('eq' => $phone))
            ->load();
        if ($getCustomer && $getCustomer->getSize() > 0) {
            return true;
        }

        $getCustomer = $this->customerFactory->getCollection()
            ->addAttributeToSelect(InstallData::PHONE_NUMBER)
            ->addAttributeToFilter(InstallData::PHONE_NUMBER, array('eq' => $phone))
            ->load();
        if ($getCustomer->getSize() > 0) {
            return true;
        }

        return false;
    }

    public function userByEmail($email)
    {
        $customer = $this->customerFactory->setWebsiteId($this->getWebsiteId());
        if ($customer->loadByEmail($email)->getId()) {
            return $customer->loadByEmail($email);
        }  else {
            return false;
        }
    }

    /**
     * @param CustomerInterface $customer
     * @param string $password Customer password.
     * @return AccountManagement
     */
    public function dispatchEvents($customer, $password)
    {
        $customerModel = $this->customerFactory->updateData($customer);
        $this->eventManager->dispatch(
            'customer_customer_authenticated',
            ['model' => $customerModel, 'password' => $password]
        );
        $this->eventManager->dispatch(
            'customer_data_object_login',
            ['customer' => $customer]
        );
        return $this;
    }

    /**
     * @param CustomerInterface $customer
     * @param string $password Customer password.
     * @return AccountManagement
     */
    public function dispatchWithoutPassEvents($customer)
    {
        $customerModel = $this->customerFactory->updateData($customer);
        $this->eventManager->dispatch(
            'customer_data_object_login',
            ['customer' => $customer]
        );
        return $this;
    }
}
