<?php

namespace TechNimbus\Stripe\Model;

use TechNimbus\Stripe\Model\Configs;
use Magento\Framework\Exception\LocalizedException;

class Client {

    protected $configs;

    /**
     * Constructor.
     * @param Configs $configs [description]
     */
    public function __construct(Configs $configs)
    {
        $this->configs = $configs;
    }

    /**
     * Initialize Stripe
     * @throws LocalizedException when the module is disabled
     */
    public function init()
    {
        if ($this->configs->isActive()) {
            \Stripe\Stripe::setApiKey($this->configs->getApiKey());
        } else {
            throw new LocalizedException(__('Stripe is disabled'));
        }
    }
}
