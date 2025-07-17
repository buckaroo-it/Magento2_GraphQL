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

namespace Buckaroo\Magento2Graphql\Model\ConfigProvider;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Configuration
{
    /**
     * XPATHs to configuration values for buckaroo_magento2_graphql
     */
    const XPATH_GRAPHQL_OVERRIDE_ENABLED = 'buckaroo_magento2_graphql/configuration/override_enabled';
    const XPATH_GRAPHQL_STATIC_PUSH_URL = 'buckaroo_magento2_graphql/configuration/static_push_url';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Check if GraphQL push URL override is enabled
     *
     * @param null|int|string $store
     * @return bool
     */
    public function isOverrideEnabled($store = null)
    {
        return (bool) $this->scopeConfig->getValue(
            self::XPATH_GRAPHQL_OVERRIDE_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get static push URL for GraphQL requests
     *
     * @param null|int|string $store
     * @return string|null
     */
    public function getStaticPushUrl($store = null)
    {
        if (!$this->isOverrideEnabled($store)) {
            return null;
        }

        $url = $this->scopeConfig->getValue(
            self::XPATH_GRAPHQL_STATIC_PUSH_URL,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return $url ? trim($url) : null;
    }

    /**
     * Check if static push URL should be used for GraphQL requests
     *
     * @param null|int|string $store
     * @return bool
     */
    public function useStaticPushUrl($store = null)
    {
        return $this->isOverrideEnabled($store) && !empty($this->getStaticPushUrl($store));
    }

    /**
     * Check if dynamic push URL should be used for GraphQL requests
     *
     * @param null|int|string $store
     * @return bool
     */
    public function useDynamicPushUrl($store = null)
    {
        return !$this->isOverrideEnabled($store);
    }
}
