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

namespace Buckaroo\Magento2Graphql\Helper;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Registry;
use Buckaroo\Magento2Graphql\Resolver\Cart\SetReturnUrl;

class GraphqlDetector
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @param RequestInterface $request
     * @param Registry $registry
     */
    public function __construct(
        RequestInterface $request,
        Registry $registry
    ) {
        $this->request = $request;
        $this->registry = $registry;
    }

    /**
     * Check if the current request is a GraphQL request
     *
     * @param mixed $transactionBuilder
     * @return bool
     */
    public function isGraphqlRequest($transactionBuilder = null)
    {
        // Method 1: Check if we're in a GraphQL context via request path
        if ($this->isGraphqlEndpoint()) {
            return true;
        }

        // Method 2: Check for GraphQL-specific markers in payment data
        if ($this->hasGraphqlPaymentMarkers($transactionBuilder)) {
            return true;
        }

        // Method 3: Check registry for GraphQL context
        if ($this->hasGraphqlRegistryMarkers()) {
            return true;
        }

        return false;
    }

    /**
     * Check if current request is to GraphQL endpoint
     *
     * @return bool
     */
    protected function isGraphqlEndpoint()
    {
        $requestUri = $this->request->getRequestUri();
        $pathInfo = $this->request->getPathInfo();

        // Check for GraphQL endpoints
        return (
            strpos($pathInfo, '/graphql') !== false ||
            strpos($requestUri, '/graphql') !== false ||
            $this->request->getHeader('Content-Type') === 'application/json' &&
            $this->request->getModuleName() === 'graphql'
        );
    }

    /**
     * Check for GraphQL-specific markers in payment data
     *
     * @param mixed $transactionBuilder
     * @return bool
     */
    protected function hasGraphqlPaymentMarkers($transactionBuilder = null)
    {
        if (!$transactionBuilder) {
            return false;
        }

        try {
            // Check if transaction builder has order with GraphQL return URL
            if (method_exists($transactionBuilder, 'getOrder')) {
                $order = $transactionBuilder->getOrder();
                if ($order && $order->getPayment()) {
                    $additionalInfo = $order->getPayment()->getAdditionalInformation();

                    // Check for the GraphQL return URL marker set by SetReturnUrl resolver
                    if (isset($additionalInfo[SetReturnUrl::ADDITIONAL_RETURN_URL])) {
                        return true;
                    }
                }
            }
        } catch (\Exception $e) {
            // If any error occurs, assume it's not GraphQL
            return false;
        }

        return false;
    }

    /**
     * Check registry for GraphQL context markers
     *
     * @return bool
     */
    protected function hasGraphqlRegistryMarkers()
    {
        // Check if GraphQL context is set in registry
        return $this->registry->registry('buckaroo_graphql_context') === true;
    }

    /**
     * Mark current context as GraphQL in registry
     *
     * @return void
     */
    public function markAsGraphqlContext()
    {
        if (!$this->registry->registry('buckaroo_graphql_context')) {
            $this->registry->register('buckaroo_graphql_context', true);
        }
    }

    /**
     * Clear GraphQL context marker from registry
     *
     * @return void
     */
    public function clearGraphqlContext()
    {
        if ($this->registry->registry('buckaroo_graphql_context')) {
            $this->registry->unregister('buckaroo_graphql_context');
        }
    }
}
