<?php

namespace Buckaroo\Magento2Graphql\Resolver\Cart;

use Buckaroo\Magento2\Api\Data\BuckarooResponseDataInterface;
use Buckaroo\Transaction\Response\TransactionResponse;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Buckaroo output of placeOrder: redirect url, transaction key and service parameters.
 *
 * Plugin 2.x keeps the gateway response of the current request in BuckarooResponseDataInterface
 * (set by its PaymentDetailsHandler) instead of the "buckaroo_response" registry entry of 1.x.
 */
class OrderOutput implements ResolverInterface
{
    /**
     * @var BuckarooResponseDataInterface
     */
    private BuckarooResponseDataInterface $buckarooResponseData;

    /**
     * @param BuckarooResponseDataInterface $buckarooResponseData
     */
    public function __construct(BuckarooResponseDataInterface $buckarooResponseData)
    {
        $this->buckarooResponseData = $buckarooResponseData;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        $response = $this->buckarooResponseData->getResponse();

        return [
            "redirect" => $this->getRedirect($response),
            "data" => $this->getAdditionalOutputData($response),
            "transaction_id" => $this->getTransactionId($response)
        ];
    }

    /**
     * Get the transaction key
     *
     * @param TransactionResponse|null $response
     *
     * @return string|null
     */
    protected function getTransactionId(?TransactionResponse $response): ?string
    {
        $key = $response !== null ? $response->get('Key') : null;

        return !empty($key) ? (string)$key : null;
    }

    /**
     * Get the redirect url
     *
     * @param TransactionResponse|null $response
     *
     * @return string|null
     */
    protected function getRedirect(?TransactionResponse $response): ?string
    {
        $requiredAction = $response !== null ? $response->get('RequiredAction') : null;

        return !empty($requiredAction['RedirectURL']) ? (string)$requiredAction['RedirectURL'] : null;
    }

    /**
     * Get the service parameters of the response
     *
     * @param TransactionResponse|null $response
     *
     * @return array|null
     */
    protected function getAdditionalOutputData(?TransactionResponse $response): ?array
    {
        $services = $response !== null ? $response->get('Services') : null;
        $parameters = $services[0]['Parameters'] ?? [];

        if (!is_array($parameters) || !count($parameters)) {
            return null;
        }

        $additionalData = [];
        foreach ($parameters as $parameter) {
            if (isset($parameter['Name'], $parameter['Value'])) {
                $additionalData[] = [
                    "key" => $parameter['Name'],
                    "value" => $parameter['Value']
                ];
            }
        }

        return $additionalData;
    }
}
