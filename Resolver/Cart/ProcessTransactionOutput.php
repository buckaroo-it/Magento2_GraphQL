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

use Buckaroo\Magento2\Api\Data\TransactionStatusResponseInterface;
use Buckaroo\Magento2\Helper\Data;
use Buckaroo\Magento2\Logging\Log;
use Magento\Framework\Encryption\Encryptor;
use Buckaroo\Magento2\Gateway\Http\Client\Json;
use Buckaroo\Magento2\Model\ConfigProvider\Account;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Buckaroo\Magento2\Api\Data\TransactionStatusResponseInterfaceFactory;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Buckaroo\Magento2\Model\Transaction\Status\ProcessResponse;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Sales\Model\Order;
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
     * @var ProcessResponse
     */
    protected $processResponse;

    /**
     * @var Json
     */
    protected $client;

    /**
     * @var TransactionStatusResponseInterfaceFactory
     */
    protected $transactionStatusResponseInterfaceFactory;

    /**
     * @var Account
     */
    protected $accountConfig;

    /**
     * @var TransactionCollection
     */
    protected $transactionCollection;

    /**
     * @var Encryptor
     */
    protected $encryptor;

    public function __construct(
        ProcessResponse $processResponse,
        TransactionStatusResponseInterfaceFactory $transactionStatusResponseInterfaceFactory,
        Json $client,
        Log $logger,
        Account $accountConfig,
        TransactionCollection $transactionCollection,
        Encryptor $encryptor
    ) {
        $this->logger = $logger;
        $this->processResponse = $processResponse;
        $this->client = $client;
        $this->transactionStatusResponseInterfaceFactory = $transactionStatusResponseInterfaceFactory;
        $this->accountConfig = $accountConfig;
        $this->transactionCollection = $transactionCollection;
        $this->encryptor = $encryptor;
    }
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
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
     * @return Order
     */
    protected function getOrder(string $transaction_id)
    {
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
     * Do buckaroo status request for transaction
     *
     * @param string $transaction_id
     *
     * @return TransactionStatusResponseInterface
     * @throws GraphQlNoSuchEntityException
     */
    protected function doRequest(string $transaction_id)
    {
        $active = $this->accountConfig->getActive();
        $mode = ($active == Data::MODE_LIVE) ?
            Data::MODE_LIVE : Data::MODE_TEST;

        $this->client->setSecretKey(
            $this->encryptor->decrypt(
                $this->accountConfig->getSecretKey()
            )
        );
        $this->client->setWebsiteKey(
            $this->encryptor->decrypt(
                $this->accountConfig->getMerchantKey()
            )
        );

        $data = $this->client->doStatusRequest($transaction_id, $mode);
        if ($data === null) {
            throw new GraphQlNoSuchEntityException(
                __('Unable to get order details')
            );
        }
        return $this->transactionStatusResponseInterfaceFactory->create(
            ["data" => $data]
        );
    }
}
