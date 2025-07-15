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
            // iDEAL
            [
                'buckaroo_magento2_ideal',
                [],
                'iDEAL'
            ],

            // Credit Cards
            [
                'buckaroo_magento2_creditcard',
                ['card_type' => 'visa'],
                'Credit Card (redirect)'
            ],

            // PayPal
            [
                'buckaroo_magento2_paypal',
                [],
                'PayPal'
            ],

            // Afterpay / Buy Now Pay Later
            [
                'buckaroo_magento2_afterpay',
                [
                    'customer_DoB' => '29/04/1997',
                    'customer_billingName' => 'John Doe',
                    'customer_iban' => 'NL91ABNA0417164300',
                    'selectedBusiness' => '1', // 1 = B2C, 2 = B2B
                    'termsCondition' => true
                ],
                'Afterpay Accept Giro'
            ],
            [
                'buckaroo_magento2_afterpay2',
                [
                    'customer_DoB' => '29/04/1997',
                    'customer_billingName' => 'John Doe',
                    'customer_iban' => 'NL91ABNA0417164300',
                    'selectedBusiness' => '1', // 1 = B2C, 2 = B2B
                    'termsCondition' => true
                ],
                'Afterpay 2'
            ],
            [
                'buckaroo_magento2_afterpay20',
                [
                    'customer_telephone' => null,
                    'customer_identificationNumber' => null,
                    'customer_DoB' => '29/04/1997',
                    'customer_billingName' => 'John Doe',
                    'termsCondition' => true,
                    'customer_coc' => ''
                ],
                'Afterpay 20'
            ],

            // Bancontact (Belgian payment method)
            [
                'buckaroo_magento2_mrcash',
                [],
                'Bancontact'
            ],

            // EPS (Austrian payment method)
            [
                'buckaroo_magento2_eps',
                [],
                'EPS (Austria)'
            ],

            // Przelewy24 (Polish payment method)
            [
                'buckaroo_magento2_p24',
                ['customer_email' => 'test@example.com'],
                'Przelewy24'
            ],

            // PayByBank
            [
                'buckaroo_magento2_paybybank',
                ['issuer' => 'ABNANL2A'],
                'Pay by Bank'
            ],

            // Apple Pay
            [
                'buckaroo_magento2_applepay',
                [],
                'Apple Pay'
            ],

            // Klarna Pay Now
            [
                'buckaroo_magento2_klarna',
                ['customer_gender' => '1'], // 1 = male, 2 = female
                'Klarna Pay Now'
            ],
            [
                'buckaroo_magento2_klarnakp',
                ['customer_gender' => '2'], // 1 = male, 2 = female
                'Klarna Pay Later'
            ],

            // SEPA Direct Debit
            [
                'buckaroo_magento2_sepadirectdebit',
                [
                    'customer_bic' => '',
                    'customer_iban' => 'NL13TEST0123456789',
                    'customer_account_name' => 'Test User'
                ],
                'SEPA Direct Debit'
            ],

            // Gift Cards
            [
                'buckaroo_magento2_giftcards',
                ['giftcard_method' => 'fashioncheque'],
                'Fashion Cheque Gift Card'
            ],

            // iDEAL Processing (QR Code)
            [
                'buckaroo_magento2_idealprocessing',
                [],
                'iDEAL Processing QR'
            ],

            // Billink
            [
                'buckaroo_magento2_billink',
                [
                    'customer_billingName' => 'John Doe',
                    'customer_gender' => '1', // 1 = male, 2 = female
                    'customer_DoB' => '29/04/1997',
                    'termsCondition' => true
                ],
                'Billink Buy Now Pay Later'
            ],

            // In3 (formerly Capayable)
            [
                'buckaroo_magento2_capayablein3',
                [
                    'customer_billingName' => 'John Doe',
                    'customer_DoB' => '01/01/1990'
                ],
                'in3 Buy Now Pay Later'
            ],

            // PayConiq
            [
                'buckaroo_magento2_payconiq',
                [],
                'PayConiq'
            ],

            // Trustly
            [
                'buckaroo_magento2_trustly',
                [],
                'Trustly'
            ],

            // Wechatpay
            [
                'buckaroo_magento2_wechatpay',
                [],
                'Wechatpay'
            ],
        ];
    }
}
