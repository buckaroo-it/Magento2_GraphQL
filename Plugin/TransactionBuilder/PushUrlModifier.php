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

namespace Buckaroo\Magento2Graphql\Plugin\TransactionBuilder;

use Buckaroo\Magento2\Gateway\Http\TransactionBuilder\Order;
use Buckaroo\Magento2Graphql\Model\ConfigProvider\Configuration;
use Buckaroo\Magento2Graphql\Helper\GraphqlDetector;
use Magento\Framework\UrlInterface;
use Buckaroo\Magento2\Logging\Log;

class PushUrlModifier
{
    /**
     * @var Configuration
     */
    protected $graphqlConfig;

    /**
     * @var GraphqlDetector
     */
    protected $graphqlDetector;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var Log
     */
    protected $logger;

    /**
     * @param Configuration $graphqlConfig
     * @param GraphqlDetector $graphqlDetector
     * @param UrlInterface $urlBuilder
     * @param Log $logger
     */
    public function __construct(
        Configuration $graphqlConfig,
        GraphqlDetector $graphqlDetector,
        UrlInterface $urlBuilder,
        Log $logger
    ) {
        $this->graphqlConfig = $graphqlConfig;
        $this->graphqlDetector = $graphqlDetector;
        $this->urlBuilder = $urlBuilder;
        $this->logger = $logger;
    }

    /**
     * Modify push URL for GraphQL requests only
     *
     * @param Order $subject
     * @param array $result
     * @return array
     */
    public function afterGetBody(Order $subject, array $result)
    {
        try {
            // Only modify if this is a GraphQL request
            if (!$this->graphqlDetector->isGraphqlRequest($subject)) {
                return $result;
            }

            // Only modify if GraphQL override is enabled
            if (!$this->graphqlConfig->isOverrideEnabled()) {
                return $result;
            }

            $this->logger->addDebug(__METHOD__ . '|GraphQL push URL override active');

            // With override enabled, we use static push URL
            if ($this->graphqlConfig->useStaticPushUrl()) {
                $result = $this->applyStaticPushUrl($result);
            } else {
                $this->logger->addDebug(__METHOD__ . '|Override enabled but no static URL configured, using dynamic');
            }

        } catch (\Exception $e) {
            $this->logger->addError(__METHOD__ . '|Error: ' . $e->getMessage());
            // Return original result if any error occurs
        }

        return $result;
    }

    /**
     * Apply static push URL to transaction body
     *
     * @param array $body
     * @return array
     */
    protected function applyStaticPushUrl(array $body)
    {
        $staticUrl = $this->graphqlConfig->getStaticPushUrl();

        if ($staticUrl) {
            $body['PushURL'] = $staticUrl;
            $this->logger->addDebug(__METHOD__ . '|Applied static push URL: ' . $staticUrl);
        } else {
            $this->logger->addDebug(__METHOD__ . '|Static push URL not configured, keeping dynamic');
        }

        return $body;
    }


}
