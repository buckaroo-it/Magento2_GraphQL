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

namespace Buckaroo\Magento2Graphql\Model;

use Magento\Framework\Phrase;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Buckaroo\Magento2Graphql\Plugin\AdditionalDataProviderPool;
use Buckaroo\Magento2Graphql\Model\Payment\Method\ConfigFactory;
use Magento\QuoteGraphQl\Model\Cart\Payment\AdditionalDataProviderInterface;

class AdditonalDataProvider implements AdditionalDataProviderInterface
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
    /**
     * Return Additional Data
     *
     * @param array $args
     * @return array
     */
    public function getData(array $args): array
    {
        $fieldKeys = $this->getFieldKeys($args);
        $additionalArgs = [];
        if(
            isset($args[AdditionalDataProviderPool::PROVIDER_KEY]) &&
            count($args[AdditionalDataProviderPool::PROVIDER_KEY])
        ) {
            foreach($args[AdditionalDataProviderPool::PROVIDER_KEY] as $argArray) {
                //filter any unkown fields
                if(in_array($argArray['key'], $fieldKeys)) {
                    $additionalArgs[$argArray['key']] = $argArray['value'];
                }
            }
        }
        unset($args[AdditionalDataProviderPool::PROVIDER_KEY]);
        return array_merge($args, $additionalArgs);
    }
    /**
     * Get field keys
     *
     * @param string $methodCode
     *
     * @return array
     * @throws GraphQlInputException
     */
    protected function getFieldKeys($args)
    {
        if (!isset($args['code'])) {
            return [];
        }

        $methodCode = $args['code'];
        try {
            $methodConfig = $this->fieldListFactory->create($methodCode);
            if ($methodConfig !== null) {
                return $methodConfig->getFieldKeys();
            }
        } catch (\Throwable $th) {
            throw new GraphQlInputException(
                new Phrase('Failed to retrieve buckaroo field keys for '.$methodCode),
                $th
            );
        }
        return [];
    }
}