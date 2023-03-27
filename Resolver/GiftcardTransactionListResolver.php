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

namespace Buckaroo\Magento2Graphql\Resolver;

use Magento\Quote\Model\Quote;

use Buckaroo\Magento2\Logging\Log;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Buckaroo\Magento2\Helper\PaymentGroupTransaction;
use Buckaroo\Magento2\Model\Giftcard\Api\ApiException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Buckaroo\Magento2Graphql\Resolver\AbstractCartResolver;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

class GiftcardTransactionListResolver extends AbstractCartResolver
{
    /**
     * @var GetCartForUser
     */
    protected $getCartForUser;

    /**
     * @var Log
     */
    private $logger;

    /**
     * @var \Buckaroo\Magento2\Helper\PaymentGroupTransaction
     */
    protected $groupTransaction;

    public function __construct(
        GetCartForUser $getCartForUser,
        Log $logger,
        PaymentGroupTransaction $groupTransaction
    ) {
        parent::__construct($getCartForUser);
        $this->logger = $logger;
        $this->groupTransaction = $groupTransaction;
    }

    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        parent::resolve($field, $context, $info, $value, $args);

        try {
            $quote = $this->getQuote($args['cart_id'], $context);
            return $this->getResponse(
                $quote
            );
        } catch (LocalizedException $e) {
            $this->logger->addDebug((string)$e);
            throw $e;
        } catch (ApiException $e) {
            $this->logger->addDebug((string)$e);
            throw new GraphQlInputException(
                __($e->getMessage())
            );
        } catch (\Throwable $th) {
            $this->logger->addDebug((string)$th);
            throw new GraphQlInputException(
                __('Unknown buckaroo error occurred')
            );
        }
    }

    protected function getResponse(Quote $quote)
    {
        return [
            'remainder_amount' => $this->getRemainderAmount($quote),
            'already_paid' => $this->getAlreadyPaid($quote),
            'transactions' => $this->getTransactions($quote),
            'quote' => $quote
        ];
    }
    /**
     * Get RemainderAmount
     *
     * @api
     * @return float
     */
    protected function getRemainderAmount(Quote $quote)
    {
        return $quote->getGrandTotal() - $this->getAlreadyPaid($quote);
    }
    /**
     * Get AlreadyPaid
     *
     * @api
     * @return float
     */
    protected function getAlreadyPaid(Quote $quote)
    {
        return $this->groupTransaction->getGroupTransactionAmount(
            $quote->getReservedOrderId()
        );
    }
    /**
     * Get the list of transactions for this cart
     *
     * @param string $cartId
     * @return array
     */
    protected function getTransactions(Quote $quote)
    {
        return $this->formatFound(
            $this->groupTransaction->getActiveItemsWithName(
                $quote->getReservedOrderId()
            )
        );
    }

    /**
     * Format data for json response
     *
     * @param array $collection
     *
     * @return array
     */
    protected function formatFound(array $collection)
    {
        return array_map(function ($item) {
            return [
                'model' => $item
            ];
        }, $collection);
    }
}
