<?php
namespace Lordhair\LoginSignup\Helper;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Helper\AbstractHelper;

/**
 * Configuration class.
 */
class Data extends AbstractHelper
{
    /**
     * Get if customer accounts are shared per website.
     *
     * @see \Magento\Customer\Model\Config\Share
     * @param string|null $scopeCode
     * @return string
     */
    public function getCustomerShareScope($scopeCode = null)
    {
        return $this->scopeConfig->getValue(
            \Magento\Customer\Model\Config\Share::XML_PATH_CUSTOMER_ACCOUNT_SHARE,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }
    
    public function getDesktopImage()
    {
        $getDesktopImage = $this->scopeConfig->getValue(
            'Lordhair_LoginSignup/loginsignup_popup_settings/loginsignup_image_desktop_upload',
            ScopeInterface::SCOPE_STORE
        );
        return $getDesktopImage;
    }

    public function getMobileImage()
    {
        $getDesktopImage = $this->scopeConfig->getValue(
            'Lordhair_LoginSignup/loginsignup_popup_settings/loginsignup_image_mobile_upload',
            ScopeInterface::SCOPE_STORE
        );
        return $getDesktopImage;
    }

    public function getFacebookSetting()
    {
        $getFacebookSetting = $this->scopeConfig->getValue(
            'Lordhair_LoginSignup/loginsignup_popup_settings/loginsignup_enable_facebook_login',
            ScopeInterface::SCOPE_STORE
        );
        return $getFacebookSetting;
    }
    
    public function getFacebookAppId()
    {
        $getFacebookAppId = $this->scopeConfig->getValue(
            'Lordhair_LoginSignup/loginsignup_popup_settings/loginsignup_facebook_app_id',
            ScopeInterface::SCOPE_STORE
        );
        return $getFacebookAppId;
    }

    public function getPaypalSetting()
    {
        $getPaypalSetting = $this->scopeConfig->getValue(
            'Lordhair_LoginSignup/loginsignup_popup_settings/loginsignup_enable_paypal_login',
            ScopeInterface::SCOPE_STORE
        );
        return $getPaypalSetting;
    }

    public function getPaypalMode()
    {
        $getPaypalMode = $this->scopeConfig->getValue(
            'Lordhair_LoginSignup/loginsignup_popup_settings/loginsignup_paypal_live_sandbox',
            ScopeInterface::SCOPE_STORE
        );
        return $getPaypalMode;
    }

    public function getPaypalClientId()
    {
        $getPaypalClientId = $this->scopeConfig->getValue(
            'Lordhair_LoginSignup/loginsignup_popup_settings/loginsignup_paypal_client_id',
            ScopeInterface::SCOPE_STORE
        );
        return $getPaypalClientId;
    }

    public function getPaypalClientSecret()
    {
        $getPaypalClientSecret = $this->scopeConfig->getValue(
            'Lordhair_LoginSignup/loginsignup_popup_settings/loginsignup_paypal_client_secret',
            ScopeInterface::SCOPE_STORE
        );
        return $getPaypalClientSecret;
    }

    public function getPaypalReturnUrl()
    {
        $getPaypalReturnUrl = $this->scopeConfig->getValue(
            'Lordhair_LoginSignup/loginsignup_popup_settings/loginsignup_paypal_return_url',
            ScopeInterface::SCOPE_STORE
        );
        return $getPaypalReturnUrl;
    }

    public function getTwilioNumber()
    {
        $getTwilioNumber = $this->scopeConfig->getValue(
            'Lordhair_LoginSignup/loginsignup_twilio_settings/loginsignup_twilio_number',
            ScopeInterface::SCOPE_STORE
        );
        return $getTwilioNumber;
    }

    public function getTwilioSid()
    {
        $getTwilioSid = $this->scopeConfig->getValue(
            'Lordhair_LoginSignup/loginsignup_twilio_settings/loginsignup_twilio_sid',
            ScopeInterface::SCOPE_STORE
        );
        return $getTwilioSid;
    }

    public function getTwilioToken()
    {
        $getTwilioToken = $this->scopeConfig->getValue(
            'Lordhair_LoginSignup/loginsignup_twilio_settings/loginsignup_twilio_token',
            ScopeInterface::SCOPE_STORE
        );
        return $getTwilioToken;
    }
}