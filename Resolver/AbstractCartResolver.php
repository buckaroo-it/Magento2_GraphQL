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


use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;

abstract class AbstractCartResolver implements ResolverInterface
{

    /**
     * @var GetCartForUser
     */
    protected $getCartForUser;


    public function __construct(
        GetCartForUser $getCartForUser
    ) {
        $this->getCartForUser = $getCartForUser;
    }

    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!(empty($args['cart_id']) || empty($args['input']['cart_id']))) {
            throw new GraphQlInputException(
                __('Required parameter "cart_id" is missing')
            );
        }

        return [];
    }
    protected function getQuote(string $maskedQuoteId, ContextInterface $context)
    {
        // Shopping Cart validation
        return $this->getCartForUser->execute(
            $maskedQuoteId,
            $context->getUserId(),
            (int)$context->getExtensionAttributes()->getStore()->getId()
        );
    }
}
