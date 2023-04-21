<?php

namespace Lordhair\Loginsignup\Observer;

use Magento\Framework\Event\ObserverInterface;

class GuestQuoteUpdate implements ObserverInterface {

    protected $checkoutSession;

    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession
    ){
        $this->checkoutSession = $checkoutSession;
    }

    public function execute(\Magento\Framework\Event\Observer $observer) {

        try {
            $checkoutmethod = (int)$this->checkoutSession->getQuote()->getCheckoutMethod();
            $customerisguest = (int)$this->checkoutSession->getQuote()->getCustomerIsGuest();

            $currentquote = $this->checkoutSession->getQuote();
    		if($checkoutmethod == 'guest'){
            	$currentquote->setCheckoutMethod('customer');
    		}
    		if($customerisguest == '1'){
    			$currentquote->setCustomerIsGuest(false);
    		}
        } catch (\Exception $e) {
           $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/UseepayGuestissue.log');
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
