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

namespace Buckaroo\Magento2Graphql\Model\Payment\Method;

 use Buckaroo\Magento2\Model\ConfigProvider\Method\Factory;

class ConfigFactory
{
    /**
     *
     * @var Factory
     */
    protected Factory $configProviderMethodFactory;

    /**
     *
     * @var array
     */
    protected array $configProviders;

    public function __construct(array $configProviders, Factory $configProviderMethodFactory)
    {
        $this->configProviderMethodFactory = $configProviderMethodFactory;
        $this->configProviders = $configProviders;
    }
    /**
     * Create MethodList field
     *
     * @param string $methodCode
     *
     * @return AbstractConfig | null
     * @throws ConfigFactoryException
     */
    public function create(string $methodCode)
    {
        if (isset($this->configProviders[$methodCode])) {
            try {
                return new $this->configProviders[$methodCode](
                    $this->configProviderMethodFactory->get($methodCode)
                );
            } catch (\Throwable $th) {
                throw new ConfigFactoryException($th->getMessage(), 0, $th);
            }
        }
        
        return null;
    }
}
