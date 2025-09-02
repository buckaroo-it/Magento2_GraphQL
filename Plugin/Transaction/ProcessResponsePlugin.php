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

namespace Buckaroo\Magento2Graphql\Plugin\Transaction;

use Buckaroo\Magento2\Api\Data\TransactionStatusResponseInterface;
use Buckaroo\Magento2\Helper\PaymentGroupTransaction;
use Buckaroo\Magento2\Logging\BuckarooLoggerInterface as BuckarooLogger;
use Buckaroo\Magento2\Model\Transaction\Status\ProcessResponse;
use Magento\Sales\Model\Order;

/**
 * Plugin to handle group transaction saving in GraphQL checkout flow for refactor branch
 *
 * @api
 */
class ProcessResponsePlugin
{
    /**
     * @var PaymentGroupTransaction
     */
    private PaymentGroupTransaction $groupTransactionHelper;

    /**
     * @var BuckarooLogger
     */
    private BuckarooLogger $logger;

    /**
     * @param PaymentGroupTransaction $groupTransactionHelper
     * @param BuckarooLogger $logger
     */
    public function __construct(
        PaymentGroupTransaction $groupTransactionHelper,
        BuckarooLogger $logger
    ) {
        $this->groupTransactionHelper = $groupTransactionHelper;
        $this->logger = $logger;
    }

    /**
     * Handle group transaction processing after successful response processing
     *
     * @param ProcessResponse $subject
     * @param array|null $result
     * @param TransactionStatusResponseInterface $response
     * @param Order $order
     * @return array|null
     */
    public function afterProcess(
        ProcessResponse $subject,
        ?array $result,
        TransactionStatusResponseInterface $response,
        Order $order
    ): ?array {
        if (!$result) {
            return $result;
        }

        // Only process successful transactions
        if (!isset($result['payment_status']) || $result['payment_status'] !== 'success') {
            return $result;
        }

        try {
            $this->processGroupTransactions($order, $response);
        } catch (\Exception $e) {
            $this->logger->addError(
                sprintf(
                    'GraphQL Group Transaction Plugin Error: %s | Order: %s',
                    $e->getMessage(),
                    $order->getIncrementId()
                )
            );
        }

        return $result;
    }

    /**
     * Process group transactions from payment additional information
     *
     * @param Order $order
     * @param TransactionStatusResponseInterface $response
     * @return void
     */
    private function processGroupTransactions(Order $order, TransactionStatusResponseInterface $response): void
    {
        $payment = $order->getPayment();
        if (!$payment) {
            return;
        }

        $allTransactions = $payment->getAdditionalInformation('buckaroo_all_transactions');
        if (!is_array($allTransactions) || count($allTransactions) <= 1) {
            return;
        }

        $this->logger->addDebug(
            sprintf(
                'Processing GraphQL Group Transactions for Order: %s | Transactions: %s',
                $order->getIncrementId(),
                json_encode(array_keys($allTransactions))
            )
        );

        foreach ($allTransactions as $transactionId => $transactionData) {
            if (!$this->isValidTransactionData($transactionData)) {
                continue;
            }

            $this->saveGroupTransaction($order, $transactionId, $transactionData, $response);
        }
    }

    /**
     * Validate transaction data structure
     *
     * @param mixed $transactionData
     * @return bool
     */
    private function isValidTransactionData($transactionData): bool
    {
        return is_array($transactionData) 
            && count($transactionData) >= 2 
            && is_string($transactionData[0]) 
            && is_numeric($transactionData[1]);
    }

    /**
     * Save individual group transaction to database
     *
     * @param Order $order
     * @param string $transactionId
     * @param array $transactionData
     * @param TransactionStatusResponseInterface $response
     * @return void
     */
    private function saveGroupTransaction(
        Order $order,
        string $transactionId,
        array $transactionData,
        TransactionStatusResponseInterface $response
    ): void {
        $serviceCode = $transactionData[0];
        $amount = (float)$transactionData[1];

        // Build response array compatible with existing saveGroupTransaction method
        $groupTransactionResponse = [
            'Invoice' => $order->getIncrementId(),
            'Key' => $transactionId,
            'ServiceCode' => $serviceCode,
            'Currency' => $order->getOrderCurrencyCode(),
            'AmountDebit' => $amount,
            'Status' => ['Code' => ['Code' => $response->getStatusCode()]],
            'RequiredAction' => [
                'PayRemainderDetails' => [
                    'GroupTransaction' => $this->extractGroupTransactionId($order)
                ]
            ],
            'RelatedTransactions' => [
                ['RelationType' => 'grouptransaction']
            ]
        ];

        $this->groupTransactionHelper->saveGroupTransaction($groupTransactionResponse);

        $this->logger->addDebug(
            sprintf(
                'GraphQL Group Transaction Saved: %s | Service: %s | Amount: %s | Order: %s',
                $transactionId,
                $serviceCode,
                $amount,
                $order->getIncrementId()
            )
        );
    }

    /**
     * Extract group transaction ID from order payment
     *
     * @param Order $order
     * @return string|null
     */
    private function extractGroupTransactionId(Order $order): ?string
    {
        $payment = $order->getPayment();
        if (!$payment) {
            return null;
        }

        // Try to get from original transaction key
        $originalKey = $payment->getAdditionalInformation('buckaroo_original_transaction_key');
        if ($originalKey) {
            return $originalKey;
        }

        // Fallback to any transaction from buckaroo_all_transactions
        $allTransactions = $payment->getAdditionalInformation('buckaroo_all_transactions');
        if (is_array($allTransactions) && !empty($allTransactions)) {
            return array_keys($allTransactions)[0];
        }

        return null;
    }
}
