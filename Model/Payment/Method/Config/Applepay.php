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
     *
     * @var \Buckaroo\Magento2\Model\ConfigProvider\Method\Applepay
     */
    protected $configProvider;

    /**
     * @inheritDoc
     */
    public function getConfig(): array
    {
        return [
            [
                "key" => "storeName",
                "value" => $this->configProvider->getStoreName()
            ],
            [
                "key" => "currency",
                "value" => $this->configProvider->getStoreCurrency()
            ],
            [
                "key" => "cultureCode",
                "value" => $this->configProvider->getCultureCode()
            ],
            [
                "key" => "country",
                "value" => $this->configProvider->getDefaultCountry()
            ],
            [
                "key" => "guid",
                "value" => $this->configProvider->getMerchantGuid()
            ],
            [
                "key" => "buttonStyle",
                "value" => $this->configProvider->getButtonStyle()
            ],
            [
                "key" => "dontAskBillingInfoInCheckout",
                "value" => (int)$this->configProvider->getDontAskBillingInfoInCheckout()
            ],
            [
                "key" => "availableButtons",
                "value" => $this->getAvailableButtons()
            ]

        ];
    }

    /**
     * Get list of available buttons
     *
     * @return string
     */
    protected function getAvailableButtons(): string
    {
        $result = '';
        $availableButtons = $this->configProvider->getAvailableButtons();
        if (is_countable($availableButtons) && count($availableButtons)) {
            $result = implode(",", $availableButtons);
        }

        return $result;
    }
}
