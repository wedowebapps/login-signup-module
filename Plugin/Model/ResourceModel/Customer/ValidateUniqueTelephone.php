<?php

namespace Lordhair\LoginSignup\Plugin\Model\ResourceModel\Customer;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\Customer as ResourceModel;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Lordhair\LoginSignup\Setup\InstallData;
use Lordhair\LoginSignup\Model\Customer as lordhairCustomerModel;

/**
 * Class ValidateUniqueTelephone
 * Validates if the customer's Telephone number is unique
 */
class ValidateUniqueTelephone
{

    /**
     * @var CustomerCollectionFactory
     */
    private $customerCollectionFactory;

    protected $_customerModel;

    /**
     * @param CustomerCollectionFactory $customerCollectionFactory
     */
    public function __construct(
        CustomerCollectionFactory $customerCollectionFactory,
        lordhairCustomerModel $customerModel
    ) {
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->_customerModel = $customerModel;
    }

    /**
     * Validates if the customer Telephone number is unique
     *
     * @param ResourceModel $subject
     * @param Customer $customer
     *
     * @throws LocalizedException
     */
    public function beforeSave(ResourceModel $subject, Customer $customer)
    { 
        if (isset($_POST['loginsignup_telephone']) && isset($_POST['loginsignup_countrycode'])) {

            $collection = $this->customerCollectionFactory->create()->addAttributeToFilter(InstallData::PHONE_NUMBER, $_POST['loginsignup_telephone']);
            $customerData = $customer->getData();
            if ($collection->getSize() > 0) {
                $getCustomrsList = $collection->getData();
                if ($getCustomrsList[0]['entity_id'] != $customerData['entity_id']) {
                    $message = "A customer with the same Telephone number already exists in an associated website. Please use another number to change.";
                    // If the customer already exists, exclude them from the query
                    if ($customer->getId()) {
                        $collection->addAttribuTeToFilter(
                            'entity_id',
                            [
                                'neq' => (int) $customer->getId(),
                            ]
                        );
                    }
                    if ($collection->getSize() > 0) {
                        throw new LocalizedException(
                            __($message)
                        );
                    }
                }
            }
        }
    }
}