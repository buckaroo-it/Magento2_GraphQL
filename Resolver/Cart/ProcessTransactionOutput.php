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

namespace Buckaroo\Magento2Graphql\Resolver\Cart;

use Buckaroo\Magento2\Logging\Log;
use Magento\Framework\Encryption\Encryptor;
use Buckaroo\Magento2\Gateway\Http\Client\Json;
use Buckaroo\Magento2\Model\ConfigProvider\Account;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Buckaroo\Magento2\Api\Data\TransactionStatusResponseInterface;
use Buckaroo\Magento2\Api\Data\TransactionStatusResponseInterfaceFactory;
use Buckaroo\Magento2\Helper\Data as BuckarooData;
use Buckaroo\Magento2\Observer\AddInTestModeMessage;
use Magento\Sales\Model\Order;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Buckaroo\Magento2\Model\Transaction\Status\ProcessResponse;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\Collection as TransactionCollection;

/**
 * Order output class
 */
class ProcessTransactionOutput implements ResolverInterface
{

    /**
     * @var Log
     */
    private $logger;


    /**
     * @var \Buckaroo\Magento2\Model\Transaction\Status\ProcessResponse
     */
    protected $processResponse;

    /**
     * @var \Buckaroo\Magento2\Gateway\Http\Client\Json
     */
    protected $client;

    /**
     * @var TransactionStatusResponseInterfaceFactory
     */
    protected $transactionResponseInterfaceFactory;

    /**
     * @var \Buckaroo\Magento2\Model\ConfigProvider\Account
     */
    protected $accountConfig;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\Collection
     */
    protected $transactionCollection;

    /**
     * @var \Magento\Framework\Encryption\Encryptor
     */
    protected $encryptor;

    public function __construct(
        ProcessResponse $processResponse,
        TransactionStatusResponseInterfaceFactory $transactionResponseInterfaceFactory,
        Json $client,
        Log $logger,
        Account $accountConfig,
        TransactionCollection $transactionCollection,
        Encryptor $encryptor
    ) {
        $this->logger = $logger;
        $this->processResponse = $processResponse;
        $this->client = $client;
        $this->transactionResponseInterfaceFactory = $transactionResponseInterfaceFactory;
        $this->accountConfig = $accountConfig;
        $this->transactionCollection = $transactionCollection;
        $this->encryptor = $encryptor;
    }
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
    ) {
        try {

            $order = $this->getOrder(
                $args['input']['transaction_id']
            );

            if ($order === null) {
                throw new GraphQlInputException(
                    __("Cannot find order based on transaction_id `{$args['input']['transaction_id']}`")
                );
            }

            if ($context->getUserId() !== (int)$order->getCustomerId()) {
                throw new GraphQlAuthorizationException(
                    __('The current user cannot perform operations on this order')
                );
            }

            return $this->processResponse->process(
                $this->doRequest(
                    $args['input']['transaction_id'],
                    $order
                ),
                $order
            );
        } catch (GraphQlAuthorizationException $e) {
            $this->logger->addDebug((string)$e);
            throw $e;
        } catch (GraphQlInputException $e) {
            $this->logger->addDebug((string)$e);
            throw $e;
        } catch (\Throwable $th) {
            $this->logger->addDebug((string)$th);
            throw new GraphQlInputException(
                __('Unknown buckaroo error occurred')
            );
        }
    }


    /**
     * Get order by transaction id
     *
     * @param string $transaction_id
     *
     * @return \Magento\Sales\Model\Order
     */
    protected function getOrder(string $transaction_id)
    {
        /** @var \Magento\Sales\Model\Order\Payment\Transaction */
        $transaction = $this->transactionCollection
            ->addFieldToFilter(
                'txn_id',
                ['eq' => $transaction_id]
            )
            ->getFirstItem();

        if ($transaction->getTxnId() !== null) {
            return $transaction->getOrder();
        }
    }

    /**
     * Do a Buckaroo status request for the transaction, with the keys and mode of the order's store
     *
     * @param string $transaction_id
     * @param Order  $order
     *
     * @return TransactionStatusResponseInterface
     * @throws GraphQlNoSuchEntityException
     */
    protected function doRequest(string $transaction_id, Order $order): TransactionStatusResponseInterface
    {
        $store = $order->getStore();

        $this->client->setSecretKey(
            $this->encryptor->decrypt(
                (string)$this->accountConfig->getSecretKey($store)
            )
        );
        $this->client->setWebsiteKey(
            $this->encryptor->decrypt(
                (string)$this->accountConfig->getMerchantKey($store)
            )
        );

        $data = $this->client->doStatusRequest($transaction_id, $this->getMode($order));
        if (!is_array($data) || empty($data)) {
            throw new GraphQlNoSuchEntityException(
                __('Unable to get order details')
            );
        }

        return $this->transactionResponseInterfaceFactory->create(
            ["data" => $data]
        );
    }

    /**
     * The mode the order was paid in; the account mode of its store when the payment did not record it
     *
     * @param Order $order
     *
     * @return int
     */
    private function getMode(Order $order): int
    {
        $payment = $order->getPayment();
        $wasTest = $payment ? $payment->getAdditionalInformation(AddInTestModeMessage::PAYMENT_IN_TEST_MODE) : null;

        if ($wasTest !== null) {
            return $wasTest ? BuckarooData::MODE_TEST : BuckarooData::MODE_LIVE;
        }

        return (int)$this->accountConfig->getActive($order->getStore()) === BuckarooData::MODE_LIVE
            ? BuckarooData::MODE_LIVE
            : BuckarooData::MODE_TEST;
    }
}
