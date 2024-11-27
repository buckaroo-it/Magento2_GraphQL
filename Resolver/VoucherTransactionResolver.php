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
use Buckaroo\Magento2\Model\Giftcard\Api\ApiException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Buckaroo\Magento2Graphql\Resolver\AbstractCartResolver;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Buckaroo\Magento2\Model\Giftcard\Response\Giftcard as GiftcardResponse;
use Buckaroo\Magento2\Model\Voucher\ApplyVoucherRequestInterface;

class VoucherTransactionResolver extends AbstractCartResolver
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
     * @var \Buckaroo\Magento2\Model\Voucher\ApplyVoucherRequestInterface
     */
    protected $voucherRequest;

    /**
     * @var \Buckaroo\Magento2\Model\Giftcard\Response\Giftcard
     */
    protected $giftcardResponse;

    public function __construct(
        GetCartForUser $getCartForUser,
        Log $logger,
        ApplyVoucherRequestInterface $voucherRequest,
        GiftcardResponse $giftcardResponse
    ) {
        parent::__construct($getCartForUser);
        $this->logger = $logger;
        $this->voucherRequest = $voucherRequest;
        $this->giftcardResponse = $giftcardResponse;
    }

    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        parent::resolve($field, $context, $info, $value, $args);

        if (!$this->argumentExists($args, 'voucher_code')) {
            throw new GraphQlInputException(__('Parameter `voucher_code` is required'));
        }

        try {
            $quote = $this->getQuote($args['input']['cart_id'], $context);
            return $this->getResponse(
                $quote,
                $this->build($quote, $args)->send()
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

    protected function getResponse(Quote $quote, $response)
    {
        $this->giftcardResponse->set($response, $quote);

        if ($this->giftcardResponse->getErrorMessage() !== null) {
            throw new ApiException($this->giftcardResponse->getErrorMessage());
        }
        return [
            'remainder_amount' => $this->giftcardResponse->getRemainderAmount(),
            'already_paid' => $this->giftcardResponse->getAlreadyPaid($quote),
            'transaction' => [
                'model' => $this->giftcardResponse->getCreatedTransaction()
            ],
            'quote' =>  $quote
        ];
    }
    /**
     * Build giftcard request
     *
     * @param array $args
     *
     * @return GiftcardRequest
     */
    protected function build(Quote $quote, array $args)
    {
        return $this->voucherRequest
            ->setVoucherCode($args['input']['voucher_code'])
            ->setQuote($quote);
    }

    protected function argumentExists($args, $name)
    {
        if (!isset($args['input'])) {
            return false;
        }
        return isset($args['input'][$name]) &&
            is_string($args['input'][$name]) &&
            strlen(trim($args['input'][$name])) > 0;
    }
}