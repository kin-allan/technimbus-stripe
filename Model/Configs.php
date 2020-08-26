<?php

namespace TechNimbus\Stripe\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use TechNimbus\Stripe\Model\Config\Source\Mode;
use TechNimbus\Stripe\Model\Config\Source\Yesno;

class Configs {

    /**
     * Path to config value, which sets if the model is active or not
     */
    CONST XML_PATH_ACTIVE  = 'payment/technimbus_stripe/active';

    /**
     * Path to config value of stripe mode
     */
    CONST XML_PATH_MODE = 'payment/technimbus_stripe/mode';

    /**
     * Path to config value of stripe live mode api key
     */
    CONST XML_PATH_LIVE_API_KEY = 'payment/technimbus_stripe/api_key';

    /**
     * Path to config value of stripe live mode publishable key
     */
    CONST XML_PATH_LIVE_PUB_KEY = 'payment/technimbus_stripe/publishable_key';

    /**
     * Path to config value of stripe test mode api key
     */
    CONST XML_PATH_TEST_API_KEY = 'payment/technimbus_stripe/test_api_key';
    /**
     * Path to config value of stripe test mode publishable key
     */
    CONST XML_PATH_TEST_PUB_KEY = 'payment/technimbus_stripe/test_publishable_key';

    /**
     * Path to config value of stripe payment title
     */
    CONST XML_PATH_TITLE = 'payment/technimbus_stripe/title';

    /**
     * Path to config value, which sets if the radar is active or not
     */
    CONST XML_PATH_RADAR = 'payment/technimbus_stripe/radar';

    /**
     * Path to config value of what types of risk levels should be set as on hold
     */
    CONST XML_PATH_RISK_LEVELS = 'payment/technimbus_stripe/risk_level';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Check if the module is active
     * @return boolean
     */
    public function isActive()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ACTIVE, ScopeInterface::SCOPE_WEBSITE) == 1;
    }

    /**
     * Get stripe mode. Type: 'test' or 'live'
     * @return string
     */
    public function getMode()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_MODE, ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * Get API Key
     * @return mixed
     */
    public function getApiKey()
    {
        $path = $this->getMode() == Mode::LIVE ? self::XML_PATH_LIVE_API_KEY : self::XML_PATH_TEST_API_KEY;
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * Get Publishable Key
     * @return mixed
     */
    public function getPublishableKey()
    {
        $path = $this->getMode() == Mode::LIVE ? self::XML_PATH_LIVE_PUB_KEY : self::XML_PATH_TEST_PUB_KEY;
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * Get Payment Title
     * @return string
     */
    public function getTitle()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_TITLE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get the base store URL
     * @return string
     */
    public function getStoreUrl()
    {
        return $this->scopeConfig->getValue('web/secure/base_url', ScopeInterface::SCOPE_STORE);
    }

    /**
     * Validate if radar is enabled
     * @return boolean
     */
    public function isRadarEnable()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_RADAR, ScopeInterface::SCOPE_WEBSITE) === Yesno::YES;
    }

    /**
     * Check what types of risk level should set the order to OnHold
     * @return array
     */
    public function getHoldRiskLevels()
    {
        return explode(",", $this->scopeConfig->getValue(self::XML_PATH_RISK_LEVELS, ScopeInterface::SCOPE_WEBSITE));
    }

    /**
     * Get Store Name
     * @return string|null the base url of the store or null if is not set
     */
    public function getStoreName()
    {
        return $this->scopeConfig->getValue('general/store_information/name', ScopeInterface::SCOPE_STORE);
    }
}
