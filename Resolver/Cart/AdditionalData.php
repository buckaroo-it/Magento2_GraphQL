<?php

namespace Buckaroo\Magento2Graphql\Resolver\Cart;

use Magento\Framework\Phrase;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Buckaroo\Magento2Graphql\Model\Payment\Method\ConfigFactory;
use Buckaroo\Magento2Graphql\Model\Payment\Method\AbstractConfig;
/**
 * 
 * methods that end up here were already validated (via ::isAvailable)
 * we can use the model to retrive additional data
 */
class AdditionalData implements ResolverInterface
{
    /**
     *
     * @var Buckaroo\Magento2Graphql\Model\Payment\Method\ConfigFactory
     */
    protected $fieldListFactory;

    public function __construct(ConfigFactory $fieldListFactory)
    {
        $this->fieldListFactory = $fieldListFactory;
    }

    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {

        $response = [
            "fields" => [],
            "config" => []
        ];
        $configClass = $this->getConfigClass($value['code']);

        if ($configClass !== null) {
            $response = [
                "fields" => $configClass->getFields(),
                "config" => $configClass->getConfig()
            ];
        }
        return $response;
    }
    /**
     * Get config class for method
     *
     * @param string $methodCode
     *
     * @return AbstractConfig|null
     */
    protected function getConfigClass($methodCode)
    {
        try {
            return $this->fieldListFactory->create($methodCode);
        } catch (\Throwable $th) {
            throw new GraphQlInputException(
                new Phrase('Failed to retrieve additional buckaroo info for '.$methodCode),
                $th
            );
        }
    }
}