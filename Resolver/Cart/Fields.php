<?php

namespace Buckaroo\Magento2Graphql\Resolver\Cart;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * 
 * methods that end up here were already validated (via ::isAvailable)
 * we can use the model to retrive additional data
 */
class Fields implements ResolverInterface
{


    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        return $value['fields'];
    }
}