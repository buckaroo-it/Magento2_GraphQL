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

use Buckaroo\Magento2\Gateway\Request\BasicParameter\PushUrlDataBuilder;
use Buckaroo\Magento2\Service\Store\PushUrlBuilder;
use Buckaroo\Magento2Graphql\Model\ConfigProvider\Configuration;
use Buckaroo\Magento2Graphql\Helper\GraphqlDetector;
use Buckaroo\Magento2\Logging\Log;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;

/**
 * Sends the configured static push URL instead of the store's push URL for GraphQL orders.
 *
 * Plugin 2.x builds every push URL through PushUrlBuilder::getPushUrl(): the payment requests
 * (PushUrlDataBuilder) as well as the giftcard, voucher and partial-payment requests. During a
 * GraphQL request that method is overridden. Requests built later for an order placed through
 * GraphQL (an admin capture or refund) are recognised from the order in PushUrlDataBuilder.
 */
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
     * @var Log
     */
    protected $logger;

    /**
     * @param Configuration $graphqlConfig
     * @param GraphqlDetector $graphqlDetector
     * @param Log $logger
     */
    public function __construct(
        Configuration $graphqlConfig,
        GraphqlDetector $graphqlDetector,
        Log $logger
    ) {
        $this->graphqlConfig = $graphqlConfig;
        $this->graphqlDetector = $graphqlDetector;
        $this->logger = $logger;
    }

    /**
     * Use the static push URL for every push URL built during a GraphQL request
     *
     * @param PushUrlBuilder $subject
     * @param string $result
     * @param mixed $store
     * @return string
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetPushUrl(PushUrlBuilder $subject, string $result, $store = null): string
    {
        try {
            if (!$this->graphqlDetector->isGraphqlRequest()) {
                return $result;
            }

            return $this->getStaticPushUrl($store) ?? $result;
        } catch (\Exception $e) {
            $this->logger->addError(__METHOD__ . '|Error: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Use the static push URL for requests about an order placed through GraphQL
     *
     * @param PushUrlDataBuilder $subject
     * @param array $result
     * @param array $buildSubject
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterBuild(PushUrlDataBuilder $subject, array $result, array $buildSubject): array
    {
        try {
            $paymentDO = $buildSubject['payment'] ?? null;
            if (!$paymentDO instanceof PaymentDataObjectInterface) {
                return $result;
            }

            $order = $paymentDO->getPayment()->getOrder();
            if (!$this->graphqlDetector->isGraphqlRequest($order)) {
                return $result;
            }

            $staticUrl = $this->getStaticPushUrl($order->getStoreId());
            if ($staticUrl !== null) {
                $result['pushURL'] = $staticUrl;
                $result['pushURLFailure'] = $staticUrl;
            }
        } catch (\Exception $e) {
            $this->logger->addError(__METHOD__ . '|Error: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * The static push URL when the override is enabled and a URL is set for the store
     *
     * @param mixed $store
     * @return string|null
     */
    private function getStaticPushUrl($store): ?string
    {
        if (!$this->graphqlConfig->useStaticPushUrl($store)) {
            return null;
        }

        $staticUrl = (string)$this->graphqlConfig->getStaticPushUrl($store);
        $this->logger->addDebug(__METHOD__ . '|Applied static push URL: ' . $staticUrl);

        return $staticUrl;
    }
}
