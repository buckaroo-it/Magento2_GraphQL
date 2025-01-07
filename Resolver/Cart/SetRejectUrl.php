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
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Buckaroo\Magento2Graphql\Resolver\AbstractCartResolver;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

class SetRejectUrl extends AbstractCartResolver
{

    public const ADDITIONAL_REJECT_URL = 'buckaroo_reject_url';
    /**
     * @var GetCartForUser
     */
    protected $getCartForUser;

    /**
     * @var Log
     */
    private $logger;

    public function __construct(
        GetCartForUser $getCartForUser,
        Log $logger
    ) {
        parent::__construct($getCartForUser);
        $this->logger = $logger;
    }

    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {


        parent::resolve($field, $context, $info, $value, $args);

        if (empty($args['input']['reject_url'])) {
            throw new GraphQlInputException(
                __('Required parameter "reject_url" is missing')
            );
        }

        $rejectUrl = $args['input']['reject_url'];
        $cartId = $args['input']['cart_id'];
        if (
            filter_var($rejectUrl, FILTER_VALIDATE_URL) === false ||
            !in_array(parse_url($rejectUrl, PHP_URL_SCHEME), ['http', 'https'])
        ) {
            throw new GraphQlInputException(
                __('A valid "reject_url" is required ')
            );
        }

        try {
            $quote = $this->getQuote($cartId, $context);
            $quote->getPayment()->setAdditionalInformation(self::ADDITIONAL_REJECT_URL, "{$rejectUrl}/{$cartId}");
        } catch (\Throwable $th) {
            $this->logger->addDebug((string)$th);
            throw new GraphQlInputException(
                __('Unknown buckaroo error occurred')
            );
        }

        return [
            "success" => true
        ];
    }
}
