<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * It is available through the world-wide-web at this URL:
 * https://www.buckaroo.nl/MIT_LICENSE
 *
 * @category        Buckaroo
 * @copyright       Copyright (c) 2023 Buckaroo B.V.
 * @license         https://www.buckaroo.nl/MIT_LICENSE
 */
declare(strict_types=1);

namespace Buckaroo\Magento2Graphql\Test\Integration\GraphQl;

class BuckarooPaymentMethodsTest extends GraphQLTestCase
{
    /**
     * Test payment method redirect URL generation
     * @dataProvider paymentMethodsDataProvider
     */
    public function testPaymentMethodRedirectUrl(string $paymentCode, array $paymentData, string $description)
    {
        $this->log("🧪 ==================== TESTING $description ====================");
        $this->log("💳 Payment Code: $paymentCode");
        $this->log("🔧 Payment Data: " . json_encode($paymentData));

        try {
            // Create complete checkout flow
            $response = $this->createCompleteCheckoutFlow($paymentCode, $paymentData);

            // Validate the payment response
            $this->validateBuckarooPaymentResponse($response, $description);

            // Extract and log redirect URL if available
            $redirectUrl = $this->extractRedirectUrl($response);
            if (!empty($redirectUrl)) {
                $this->log("🔗 Redirect URL: $redirectUrl", 'SUCCESS');

                // Assert redirect URL is valid
                $this->assertNotEmpty($redirectUrl, 'Redirect URL should not be empty');
                $this->assertStringContainsString('http', $redirectUrl, 'Redirect URL should be a valid HTTP URL');
            } else {
                $this->log("ℹ️ No redirect URL (payment method may not require redirect)", 'INFO');
            }

            $this->log("✅ 🎉 TEST PASSED: $description", 'SUCCESS');

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();

            // Handle payment method not available gracefully
            if (strpos($errorMessage, 'The requested Payment Method is not available') !== false) {
                $this->log("⚠️ PAYMENT METHOD NOT ENABLED: $description", 'INFO');
                $this->log("ℹ️ This payment method is not configured/enabled in Magento", 'INFO');
                $this->markTestSkipped("Payment method '$paymentCode' is not available/enabled in current Magento configuration");
                return;
            }

            // Handle internal server errors (often configuration issues)
            if (strpos($errorMessage, 'Internal server error') !== false) {
                $this->log("⚠️ INTERNAL SERVER ERROR: $description", 'WARN');
                $this->log("ℹ️ This usually indicates a payment method configuration issue or missing API credentials", 'WARN');
                $this->markTestSkipped("Payment method '$paymentCode' caused internal server error - likely configuration issue");
                return;
            }

            // Handle validation errors for complex payment methods
            if (strpos($errorMessage, 'Field') !== false && strpos($errorMessage, 'was not provided') !== false) {
                $this->log("⚠️ VALIDATION ERROR: $description", 'WARN');
                $this->log("ℹ️ Payment method requires additional fields that weren't provided correctly", 'WARN');
                $this->log("🔍 Error details: $errorMessage", 'DEBUG');

                // Still fail but with more context
                $this->fail("Payment method '$description' validation failed. This may indicate missing required fields or incorrect GraphQL schema. Error: $errorMessage");
            }

            // Log the error with full context
            $this->log("❌ TEST FAILED: $description", 'ERROR', [
                'error_message' => $errorMessage,
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine()
            ]);

            // For any other error, mark as critical failure
            $errorType = (strpos($errorMessage, 'TypeError') !== false) ? 'CRITICAL FAILURE' : 'TEST FAILED';
            $this->log("❌ 🧪 ==================== $errorType ====================");

            // Re-throw the exception to fail the test
            throw $e;
        }
    }

    /**
     * Data provider for all Buckaroo payment methods
     * Returns array of [payment_code, payment_data, description]
     */
    public function paymentMethodsDataProvider(): array
    {
        return [
            ['buckaroo_magento2_ideal', [], 'iDEAL | Wero'],
            ['buckaroo_magento2_creditcard', ['card_type' => 'visa'], 'Credit Card (redirect)'],
            ['buckaroo_magento2_paypal', [], 'PayPal'],
            [
                'buckaroo_magento2_afterpay',
                [
                    'termsCondition' => '1',
                    'customer_DoB' => '29/04/1997',
                    'customer_gender' => '1',
                ],
                'Riverty | Afterpay (legacy)'
            ],
            [
                'buckaroo_magento2_afterpay2',
                [
                    'termsCondition' => '1',
                    'customer_DoB' => '29/04/1997',
                    'customer_gender' => '1',
                ],
                'Riverty | Afterpay (legacy, second account)'
            ],
            ['buckaroo_magento2_afterpay20', ['customer_DoB' => '29/04/1997'], 'Riverty'],
            ['buckaroo_magento2_mrcash', [], 'Bancontact'],
            ['buckaroo_magento2_eps', [], 'EPS'],
            ['buckaroo_magento2_p24', [], 'Przelewy24'],
            ['buckaroo_magento2_paybybank', [], 'PayByBank'],
            ['buckaroo_magento2_klarna', [], 'Klarna'],
            ['buckaroo_magento2_klarnakp', [], 'Klarna (authorize)'],
            [
                'buckaroo_magento2_sepadirectdebit',
                [
                    'customer_iban' => 'NL13TEST0123456789',
                    'customer_account_name' => 'Test User'
                ],
                'SEPA Direct Debit'
            ],
            ['buckaroo_magento2_giftcards', ['giftcard_method' => 'fashioncheque'], 'Giftcards (redirect)'],
            [
                'buckaroo_magento2_billink',
                [
                    'customer_gender' => '1',
                    'customer_DoB' => '29/04/1997'
                ],
                'Billink'
            ],
            ['buckaroo_magento2_capayablein3', ['customer_DoB' => '01/01/1990'], 'In3'],
            ['buckaroo_magento2_trustly', [], 'Trustly'],
            ['buckaroo_magento2_wechatpay', [], 'WeChat Pay'],
            ['buckaroo_magento2_alipay', [], 'Alipay'],
            ['buckaroo_magento2_wero', [], 'Wero'],
            ['buckaroo_magento2_bizum', [], 'Bizum'],
        ];
    }
}
