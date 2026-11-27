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

declare(strict_types=1);

namespace Buckaroo\Magento2Graphql\Resolver\Cart;

use Buckaroo\Magento2\Logging\Log;
use Buckaroo\Magento2Graphql\Model\Payment\MethodConfig;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * available_payment_methods.buckaroo_additional: the checkout configuration of a Buckaroo method
 */
class AdditionalData implements ResolverInterface
{
    /**
     * @var MethodConfig
     */
    private MethodConfig $methodConfig;

    /**
     * @var Log
     */
    private Log $logger;

    /**
     * @param MethodConfig $methodConfig
     * @param Log $logger
     */
    public function __construct(MethodConfig $methodConfig, Log $logger)
    {
        $this->methodConfig = $methodConfig;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        $methodCode = (string)($value['code'] ?? '');

        try {
            return $this->methodConfig->get($methodCode);
        } catch (\Throwable $th) {
            // One method's configuration must never break the whole available_payment_methods query
            $this->logger->addError(__METHOD__ . '|' . $methodCode . '|' . $th->getMessage());
            return [];
        }
    }
}
