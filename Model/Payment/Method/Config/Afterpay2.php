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

class Afterpay2 extends AbstractConfig
{
    /**
     *
     * @var \Buckaroo\Magento2\Model\ConfigProvider\Method\Afterpay2
     */
    protected $configProvider;

    /**
     * @inheritDoc
     */
    public function getConfig()
    {
        return [
            [
                "key"=>"businessMethod",
                "value" => $this->configProvider->getBusiness()
            ],
            [
                "key"=>"paymentMethod",
                "value" => $this->configProvider->getPaymentMethod()
            ]
        ];
    }
}
