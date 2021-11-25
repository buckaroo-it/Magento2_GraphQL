<?php

namespace Buckaroo\Magento2Graphql\Resolver\Cart;

use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
//example - use case
use Buckaroo\Magento2\Model\ConfigProvider\Method\Ideal;
/**
 * 
 * methods that end up here were already validated (via ::isAvailable)
 * we can use the model to retr
 */
class AvailableIssuersForMethod implements ResolverInterface
{
    private $idealConfig;

    public function __construct(Ideal $ideal)
    {
        $this->idealConfig = $ideal;
    }

    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $storeId = $context->getExtensionAttributes()->getStore()->getId();
        $method = $value['code'];
        $parts = explode("buckaroo_magento2_",$method);
        $available_banks = [];
        if(isset($parts[1])){
            $buckaroo_method_code = $parts[1];
            //get the right data - this should be refactored and abstracted
            if($buckaroo_method_code == 'ideal') {
                $config = $this->idealConfig->getConfig();        
                $available_banks = $config['payment']['buckaroo']['ideal']['banks'];
            } else {
                $available_banks = [];
            }
        }

        return $available_banks;
    }
}