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
use Buckaroo\Magento2Graphql\Helper\GraphqlDetector;

class SetReturnUrl extends AbstractCartResolver
{

    public const ADDITIONAL_RETURN_URL = 'buckaroo_return_url';
    /**
     * @var GetCartForUser
     */
    protected $getCartForUser;

    /**
     * @var Log
     */
    private $logger;

    /**
     * @var GraphqlDetector
     */
    private $graphqlDetector;

    public function __construct(
        GetCartForUser $getCartForUser,
        Log $logger,
        GraphqlDetector $graphqlDetector
    ) {
        parent::__construct($getCartForUser);
        $this->logger = $logger;
        $this->graphqlDetector = $graphqlDetector;
    }

    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        // Mark this as a GraphQL context for push URL detection
        $this->graphqlDetector->markAsGraphqlContext();

        parent::resolve($field, $context, $info, $value, $args);

        if (empty($args['input']['return_url'])) {
            throw new GraphQlInputException(
                __('Required parameter "return_url" is missing')
            );
        }

        $returnUrl = $args['input']['return_url'];
        $cartId = $args['input']['cart_id'];
        if (
            filter_var($returnUrl, FILTER_VALIDATE_URL) === false ||
            !in_array(parse_url($returnUrl, PHP_URL_SCHEME), ['http', 'https'])
        ) {
            throw new GraphQlInputException(
                __('A valid "return_url" is required ')
            );
        }

        try {
            $quote = $this->getQuote($cartId, $context);
            $quote->getPayment()->setAdditionalInformation(self::ADDITIONAL_RETURN_URL, "{$returnUrl}/{$cartId}");
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
