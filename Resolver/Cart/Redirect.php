<?php

namespace Buckaroo\Magento2Graphql\Resolver\Cart;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Order output redirect
 */
class Redirect implements ResolverInterface
{
    public function __construct(\Magento\Framework\Registry $registry) {
        $this->registry = $registry;
    }
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {

        if ($this->registry && $this->registry->registry('buckaroo_response')) {
            $data = $this->registry->registry('buckaroo_response')[0];
            if(!empty($data->RequiredAction->RedirectURL)) {
                return $data->RequiredAction->RedirectURL;
            }
        }
        return $value['redirect'];
    }
}