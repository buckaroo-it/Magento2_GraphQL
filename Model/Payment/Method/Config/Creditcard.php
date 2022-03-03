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

class Creditcard extends AbstractConfig
{
    /**
     * @inheritDoc
     */
    public function getConfig()
    {
        return [
            [
                "key"=>"cards",
                "value" => $this->getConfigValue('cards')
            ],
            [
                "key"=>"selectionType",
                "value" => $this->getConfigValue('selectionType')
            ],
            [
                "key"=>"paymentFlow",
                "value" => $this->getConfigValue('paymentFlow')
            ]
        ];
    }
    protected function getConfigValue($key)
    {
       return $this->configProvider->getConfig()['payment']['buckaroo']['creditcard'][$key];
    }
}