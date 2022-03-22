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

class Applepay extends AbstractConfig
{
    /**
     * @inheritDoc
     */
    public function getConfig()
    {
        return [
            [
                "key" => "storeName",
                "value" => $this->getConfigValue('storeName')
            ],
            [
                "key" => "currency",
                "value" => $this->getConfigValue('currency')
            ],
            [
                "key" => "cultureCode",
                "value" => $this->getConfigValue('cultureCode')
            ],
            [
                "key" => "country",
                "value" => $this->getConfigValue('country')
            ],
            [
                "key" => "guid",
                "value" => $this->getConfigValue('guid')
            ],
            [
                "key" => "buttonStyle",
                "value" => $this->getConfigValue('buttonStyle')
            ],
            [
                "key" => "dontAskBillingInfoInCheckout",
                "value" => $this->getConfigValue('dontAskBillingInfoInCheckout')
            ],
            [
                "key" => "availableButtons",
                "value" => $this->getAvailableButtons()
            ]
           
        ];
    }
    protected function getConfigValue($key)
    {
        return $this->configProvider->getConfig()['payment']['buckaroo']['applepay'][$key];
    }
    /**
     * Get list of available buttons
     *
     * @return void
     */
    protected function getAvailableButtons()
    {
        $availableButtons = $this->getConfigValue('availableButtons');
        if (count($availableButtons)) {
            return implode(",", $availableButtons);
        }
    }
}
