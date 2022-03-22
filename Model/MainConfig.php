<?php

/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * It is available through the world-wide-web at this URL:
 * https://tldrlegal.com/license/mit-license
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to support@buckaroo.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact support@buckaroo.nl for more information.
 *
 * @copyright Copyright (c) Buckaroo B.V.
 * @license   https://tldrlegal.com/license/mit-license
 */

namespace Buckaroo\Magento2Graphql\Model;

use Magento\Store\Model\StoreManagerInterface;
use \Magento\Framework\App\Config\ScopeConfigInterface;

class MainConfig
{

    const XPATH_ENABLE_GRAPHQL             = 'buckaroo_magento2/graphql/enable_graphql';
    const XPATH_BASE_URL                   = 'buckaroo_magento2/graphql/base_url';
    const XPATH_PAYMENT_PROCESSED_REDIRECT = 'buckaroo_magento2/graphql/payment_processed_redirect';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    public function isGraphQlEnabled()
    {
        return $this->getValue(self::XPATH_ENABLE_GRAPHQL) == 1;
    }
    public function getBaseUrl()
    {
        $baseUrl = $this->getValue(self::XPATH_BASE_URL);
        if (is_string($baseUrl) && filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            return rtrim($baseUrl, "/");
        }
    }
    public function getPaymentProcessedPath()
    {
        return ltrim($this->getValue(self::XPATH_PAYMENT_PROCESSED_REDIRECT), "/");
    }

    protected function getValue($xpath)
    {
        return $this->scopeConfig->getValue(
            $xpath,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()
        );
    }
}
