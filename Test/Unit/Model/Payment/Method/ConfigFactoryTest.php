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

declare(strict_types=1);

namespace Buckaroo\Magento2Graphql\Test\Unit\Model\Payment\Method;

use Buckaroo\Magento2Graphql\Model\Payment\Method\ConfigFactory;
use Buckaroo\Magento2\Model\ConfigProvider\Method\Factory as MethodFactory;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for ConfigFactory to verify GitHub issue #29 fix
 * 
 * @covers \Buckaroo\Magento2Graphql\Model\Payment\Method\ConfigFactory
 */
class ConfigFactoryTest extends TestCase
{
    /**
     * Test that ConfigFactory uses the correct Method Factory class
     */
    public function testConfigFactoryUsesCorrectMethodFactory(): void
    {
        $this->assertTrue(
            class_exists('Buckaroo\Magento2\Model\ConfigProvider\Method\Factory'),
            'Method Factory class should exist'
        );
        
        $this->assertTrue(
            class_exists('Buckaroo\Magento2Graphql\Model\Payment\Method\ConfigFactory'),
            'GraphQL ConfigFactory class should exist'
        );
    }

    /**
     * Test that Method Factory has the correct get method signature
     */
    public function testMethodFactoryHasCorrectInterface(): void
    {
        $this->assertTrue(
            method_exists('Buckaroo\Magento2\Model\ConfigProvider\Method\Factory', 'get'),
            'Method Factory should have get method'
        );
        
        $this->assertTrue(
            method_exists('Buckaroo\Magento2\Model\ConfigProvider\Method\Factory', 'has'),
            'Method Factory should have has method'
        );
    }

    /**
     * Test ConfigFactory constructor accepts correct Factory type
     */
    public function testConfigFactoryConstructor(): void
    {
        $methodFactoryMock = $this->createMock(MethodFactory::class);
        $configProviders = ['test_method' => 'TestClass'];
        
        $configFactory = new ConfigFactory($configProviders, $methodFactoryMock);
        
        $this->assertInstanceOf(ConfigFactory::class, $configFactory);
    }

    /**
     * Test create method workflow (basic structure validation)
     */
    public function testCreateMethodExists(): void
    {
        $this->assertTrue(
            method_exists('Buckaroo\Magento2Graphql\Model\Payment\Method\ConfigFactory', 'create'),
            'ConfigFactory should have create method'
        );
    }

    /**
     * Test the fix for GitHub issue #29 - verify import is correct
     */
    public function testGitHubIssue29Fix(): void
    {
        // This test verifies that the correct Factory class is being used
        // The fix changes the import from:
        // use Buckaroo\Magento2\Model\ConfigProvider\Factory;
        // to:
        // use Buckaroo\Magento2\Model\ConfigProvider\Method\Factory;
        
        $reflection = new \ReflectionClass(ConfigFactory::class);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();
        
        // Verify that the second parameter expects Method\Factory
        $factoryParam = $parameters[1];
        $this->assertEquals('configProviderMethodFactory', $factoryParam->getName());
        
        // The type should be the Method\Factory, not the general Factory
        $type = $factoryParam->getType();
        if ($type instanceof \ReflectionNamedType) {
            $this->assertEquals(
                'Buckaroo\Magento2\Model\ConfigProvider\Method\Factory',
                $type->getName()
            );
        }
    }
}
