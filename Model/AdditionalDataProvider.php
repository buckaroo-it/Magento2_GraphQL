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

use Buckaroo\Magento2Graphql\Plugin\AdditionalDataProviderPool;
use Buckaroo\Magento2Graphql\Model\Payment\Method\ConfigFactory;
use Magento\QuoteGraphQl\Model\Cart\Payment\AdditionalDataProviderInterface;

class AdditionalDataProvider implements AdditionalDataProviderInterface
{
    const PAYMENT_FROM = 'buckaroo_payment_from';
    /**
     *
     * @var ConfigFactory
     */
    protected $fieldListFactory;

    public function __construct(ConfigFactory $fieldListFactory)
    {
        $this->fieldListFactory = $fieldListFactory;
    }
    /**
     * Return Additional Data,
     * set a flag so we know the payment originated from graphql
     *
     * @param array $args
     * @return array
     */
    public function getData(array $args): array
    {
        $args[self::PAYMENT_FROM] = 'graphQl';

        if (isset($args[AdditionalDataProviderPool::PROVIDER_KEY][$args['code']])) {

            $additionalArgs = $args[AdditionalDataProviderPool::PROVIDER_KEY][$args['code']];
            unset($args[AdditionalDataProviderPool::PROVIDER_KEY]);

            return array_merge($args, $additionalArgs);
        }

        return $args;
    }
}
