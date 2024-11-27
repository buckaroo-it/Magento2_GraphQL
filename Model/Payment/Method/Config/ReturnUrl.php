<?php

namespace Buckaroo\Magento2Graphql\Model\Payment\Method\Config;

class ReturnUrl
{
    protected $returnUrl;

    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig)
    {
        // Fetch the return URL from system configuration, or set a default value.
        $this->returnUrl = $scopeConfig->getValue(
            'payment/buckaroo/return_url',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        // Or you can set a default URL if not configured
        if (!$this->returnUrl) {
            $this->returnUrl = "https://default-return-url.com";
        }
    }

    public function getReturnUrl()
    {
        return $this->returnUrl;
    }
}