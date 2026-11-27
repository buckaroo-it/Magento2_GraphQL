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

namespace Buckaroo\Magento2Graphql\Test\Unit\Resolver\Cart;

use Buckaroo\Magento2\Api\Data\BuckarooResponseDataInterface;
use Buckaroo\Magento2Graphql\Resolver\Cart\OrderOutput;
use Buckaroo\Transaction\Response\TransactionResponse;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use PHPUnit\Framework\TestCase;

class OrderOutputTest extends TestCase
{
    public function testAnswersForTheOrderPlacedInThisRequest(): void
    {
        $result = $this->resolve($this->response(['Invoice' => '300000001']), ['order_number' => '300000001']);

        $this->assertSame('KEY1', $result['transaction_id']);
        $this->assertSame('https://checkout/redirect', $result['redirect']);
    }

    public function testReturnsNullForAnotherOrder(): void
    {
        $this->assertNull($this->resolve($this->response(['Invoice' => '300000001']), ['number' => '300000099']));
    }

    public function testReturnsNullWithoutResponse(): void
    {
        $this->assertNull($this->resolve(null, ['number' => '300000001']));
    }

    public function testMatchesAResponseWithoutInvoiceByTheTransactionKeyOnThePayment(): void
    {
        $payment = $this->createMock(Payment::class);
        $payment->method('getAdditionalInformation')->with('buckaroo_original_transaction_key')->willReturn('KEY1');
        $order = $this->createMock(Order::class);
        $order->method('getPayment')->willReturn($payment);

        $this->assertSame('KEY1', $this->resolve($this->response([]), ['number' => '300000001', 'model' => $order])['transaction_id']);
    }

    private function response(array $data): TransactionResponse
    {
        return new TransactionResponse(null, array_merge([
            'Key' => 'KEY1',
            'RequiredAction' => ['RedirectURL' => 'https://checkout/redirect', 'Name' => 'Redirect'],
        ], $data));
    }

    private function resolve(?TransactionResponse $response, array $order): ?array
    {
        $data = $this->createMock(BuckarooResponseDataInterface::class);
        $data->method('getResponse')->willReturn($response);

        return (new OrderOutput($data))->resolve(
            $this->createMock(Field::class),
            null,
            $this->createMock(ResolveInfo::class),
            $order
        );
    }
}
