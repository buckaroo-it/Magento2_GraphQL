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

namespace Buckaroo\Magento2Graphql\Test\Unit\Model;

use Buckaroo\Magento2Graphql\Model\AdditionalDataProvider;
use PHPUnit\Framework\TestCase;

class AdditionalDataProviderTest extends TestCase
{
    public function testUsesTheFieldNamedAfterTheMethodCode(): void
    {
        $data = (new AdditionalDataProvider())->getData([
            'code' => 'buckaroo_magento2_sepadirectdebit',
            'buckaroo_magento2_sepadirectdebit' => ['customer_iban' => 'NL13TEST0123456789'],
            'buckaroo_magento2_ideal' => ['issuer' => 'not-for-this-method'],
        ]);

        $this->assertSame(['customer_iban' => 'NL13TEST0123456789', 'buckaroo_payment_from' => 'graphQl'], $data);
    }

    public function testMethodWithoutFieldsOnlyGetsTheOrigin(): void
    {
        $this->assertSame(
            ['buckaroo_payment_from' => 'graphQl'],
            (new AdditionalDataProvider())->getData(['code' => 'buckaroo_magento2_paypal'])
        );
    }
}
