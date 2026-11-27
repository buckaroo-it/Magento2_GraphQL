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

namespace Buckaroo\Magento2Graphql\Test\Unit\Model\Payment;

use Buckaroo\Magento2\Model\ConfigProvider\Method\AbstractConfigProvider;
use Buckaroo\Magento2\Model\ConfigProvider\Method\Factory;
use Buckaroo\Magento2Graphql\Model\Payment\MethodConfig;
use PHPUnit\Framework\TestCase;

class MethodConfigTest extends TestCase
{
    public function testFlattensThePluginCheckoutConfigOfTheMethod(): void
    {
        $config = $this->methodConfig('buckaroo_magento2_creditcards', [
            'title' => 'Cards',
            'isTestMode' => true,
            'redirecturl' => 'https://shop/buckaroo/mrcash/pay?form_key=abc',
            'allowedCurrencies' => ['EUR', 'USD'],
            'creditcards' => [
                ['name' => 'Visa', 'code' => 'visa', 'img' => 'https://shop/visa.svg', 'sort' => 1],
                ['name' => 'No code'],
            ],
            'genderList' => [['genderType' => 'male', 'genderTitle' => 'He/him']],
            'placeholders' => ['cardNumber' => '1234'],
            'logo' => null,
        ]);

        $this->assertSame([
            ['key' => 'title', 'value' => 'Cards'],
            ['key' => 'isTestMode', 'value' => '1'],
            ['key' => 'allowedCurrencies', 'value' => 'EUR,USD'],
            ['key' => 'creditcards', 'values' => [['code' => 'visa', 'name' => 'Visa', 'img' => 'https://shop/visa.svg']]],
            ['key' => 'genderList', 'values' => [['code' => 'male', 'name' => 'He/him', 'img' => null]]],
            ['key' => 'placeholders.cardNumber', 'value' => '1234'],
        ], $config->get('buckaroo_magento2_creditcards'));
    }

    public function testReturnsNothingForOtherOrUnknownMethods(): void
    {
        $factory = $this->createMock(Factory::class);
        $factory->method('has')->willReturn(false);
        $factory->expects($this->never())->method('get');
        $config = new MethodConfig($factory);

        $this->assertSame([], $config->get('checkmo'));
        $this->assertSame([], $config->get('buckaroo_magento2_unknown'));
    }

    private function methodConfig(string $code, array $methodConfig): MethodConfig
    {
        $provider = $this->createMock(AbstractConfigProvider::class);
        $provider->method('getConfig')->willReturn(['payment' => ['buckaroo' => [$code => $methodConfig]]]);
        $factory = $this->createMock(Factory::class);
        $factory->method('has')->willReturn(true);
        $factory->method('get')->with($code)->willReturn($provider);

        return new MethodConfig($factory);
    }
}
