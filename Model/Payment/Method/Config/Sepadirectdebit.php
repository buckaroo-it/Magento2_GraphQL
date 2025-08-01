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

namespace Buckaroo\Magento2Graphql\Model\Payment\Method\Config;

use Buckaroo\Magento2Graphql\Model\Payment\Method\AbstractConfig;

class Sepadirectdebit extends AbstractConfig
{
    /**
     *
     * @var \Buckaroo\Magento2\Model\ConfigProvider\Method\Sepadirectdebit
     */
    protected $configProvider;

    /**
     * @inheritDoc
     */
    public function getConfig()
    {
        return [
            [
                "key" => "customer_iban",
                "required" => true
            ],
            [
                "key" => "customer_bic",
                "required" => false
            ],
            [
                "key" => "customer_account_name",
                "required" => true
            ]
        ];
    }

    protected function getConfigValue($key)
    {
        return $this->configProvider->getConfig()['payment']['buckaroo']['sepadirectdebit'][$key] ?? null;
    }
}
