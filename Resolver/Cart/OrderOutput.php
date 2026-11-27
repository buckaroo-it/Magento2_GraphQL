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

        // The field is also on CustomerOrder (orderV2, customer order history): only answer for the
        // order whose Buckaroo response belongs to this request.
        if ($response === null || !$this->isResponseForOrder($response, $value ?? [])) {
            return null;
        }

        return [
            "redirect" => $this->getRedirect($response),
            "data" => $this->getAdditionalOutputData($response),
            "transaction_id" => $this->getTransactionId($response)
        ];
    }

    /**
     * Whether the Buckaroo response belongs to the order being resolved
     *
     * @param TransactionResponse $response
     * @param array $order Order (placeOrder.order) or CustomerOrder (orderV2) value
     *
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function isResponseForOrder(TransactionResponse $response, array $order): bool
    {
        $orderModel = isset($order['model']) && is_object($order['model']) ? $order['model'] : null;
        $orderNumber = $order['order_number'] ?? $order['number']
            ?? ($orderModel && method_exists($orderModel, 'getIncrementId') ? $orderModel->getIncrementId() : null);

        $invoice = $response->get('Invoice');
        if (!empty($invoice)) {
            return $orderNumber !== null && (string)$invoice === (string)$orderNumber;
        }

        // Some first responses (e.g. the Klarna reserve) carry no Invoice: match the transaction key
        // stored on the order's payment instead.
        if ($orderModel && method_exists($orderModel, 'getPayment') && $orderModel->getPayment()) {
            $key = (string)$response->get('Key');
            $payment = $orderModel->getPayment();

            return $key !== '' && in_array($key, [
                (string)$payment->getAdditionalInformation('buckaroo_original_transaction_key'),
                (string)$payment->getLastTransId(),
            ], true);
        }

        // placeOrder.order (deprecated Order type) only ever resolves the order placed in this request
        return isset($order['order_number']);
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
