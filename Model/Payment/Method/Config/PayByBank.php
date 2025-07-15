<?php

namespace Buckaroo\Magento2Graphql\Model\Payment\Method\Config;

use Buckaroo\Magento2Graphql\Model\Payment\Method\AbstractConfig;

class PayByBank extends AbstractConfig
{
    /**
     *
     * @var \Buckaroo\Magento2\Model\ConfigProvider\Method\PayByBank
     */
    protected $configProvider;

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        $config = $this->configProvider->getConfig();
        $payByBankConfig = $config['payment']['buckaroo']['paybybank'] ?? [];

        return [
            [
                "key" => "banks",
                "values" => $payByBankConfig['banks'] ?? []
            ],
            [
                "key" => "selectionType",
                "value" => $payByBankConfig['selectionType'] ?? null
            ]
        ];
    }

    /**
     * Get bank list
     *
     * @return array
     */
    protected function getBanks()
    {
        if ($this->configProvider->hasIssuers()) {
            return $this->configProvider->getIssuers();
        }
        return [];
    }

    /**
     * Get payment flow
     *
     * @return string
     */
    public function getPaymentFlow()
    {
        return $this->configProvider->getPaymentFlow();
    }
}
