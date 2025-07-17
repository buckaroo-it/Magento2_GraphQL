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

use PHPUnit\Framework\TestCase;

abstract class GraphQLTestCase extends TestCase
{
    /**
     * @var bool Enable detailed logging
     */
    protected $enableLogging = true;

    /**
     * @var array Log entries for debugging
     */
    protected $logEntries = [];

    /**
     * @var string Magento GraphQL endpoint URL
     */
    protected $graphqlUrl = 'https://magento.test/graphql';

    /**
     * @var string Default product SKU for testing
     */
    protected $defaultProductSku = '24-MB01'; // Joust Duffle Bag from sample data

    /**
     * @var bool Whether to use real API or simulation
     */
    protected $useRealApi = false;

    /**
     * @var string Working GraphQL URL
     */
    protected $workingGraphqlUrl = null;

    /**
     * @var array
     */
    protected $logContext = [];

    protected function setUp(): void
    {
        // Setup for standalone testing without Magento test framework
        parent::setUp();
        $this->logEntries = [];
        $this->log("🚀 Starting test setup", 'INFO');

        // Try to find a working GraphQL endpoint
        $this->detectWorkingApiEndpoint();
    }

    /**
     * Detect working API endpoint or fall back to simulation
     */
    protected function detectWorkingApiEndpoint(): void
    {
        if ($this->testApiConnection($this->graphqlUrl)) {
            $this->workingGraphqlUrl = $this->graphqlUrl;
            $this->useRealApi = true;
            $this->log("✅ Connected to real GraphQL API", 'SUCCESS', ['url' => $this->graphqlUrl]);
            return;
        }

        $this->useRealApi = false;
        $this->log("❌ No GraphQL API available. Please ensure Magento is running and accessible.", 'ERROR');
    }

    /**
     * Test API connection to a given URL
     */
    protected function testApiConnection(string $url): bool
    {
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode(['query' => 'query { __typename }']),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Store: default'
                ],
                CURLOPT_TIMEOUT => 5,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_FOLLOWLOCATION => true,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            return !$error && $httpCode > 0 && $httpCode < 500;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Log message with timestamp and level
     *
     * @param string $message
     * @param string $level
     * @param array $context
     */
    protected function log(string $message, string $level = 'INFO', array $context = []): void
    {
        if (!$this->enableLogging) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s.000');
        $emoji = match ($level) {
            'DEBUG' => '🔍',
            'SUCCESS' => '✅',
            'ERROR' => '❌',
            'WARNING' => '⚠️',
            default => 'ℹ️'
        };

        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        $logEntry = "[$timestamp] [$level] $emoji $message$contextStr";
        $this->logEntries[] = $logEntry;
        echo $logEntry . "\n";
    }

    protected function getLogEntries(): array
    {
        return $this->logEntries;
    }

    protected function clearLog(): void
    {
        $this->logEntries = [];
    }

    /**
     * Execute a GraphQL mutation using real HTTP request or simulation
     */
    protected function graphQlMutation(
        string $query,
        array  $variables = [],
        string $operationName = '',
        array  $headers = []
    ): array
    {
        return $this->graphQlQuery($query, $variables, $operationName, $headers);
    }

    /**
     * Execute GraphQL query using real HTTP request or simulation fallback
     */
    protected function graphQlQuery(
        string $query,
        array  $variables = [],
        string $operationName = '',
        array  $headers = []
    ): array
    {
        $this->log("📡 Executing GraphQL query", 'DEBUG', [
            'operation' => $operationName ?: 'Unknown',
            'variables_count' => count($variables),
            'headers_count' => count($headers),
            'mode' => $this->useRealApi ? 'real_api' : 'simulation'
        ]);

        if ($this->useRealApi && $this->workingGraphqlUrl) {
            return $this->executeRealGraphQLQuery($query, $variables, $operationName, $headers);
        }

        throw new \Exception('No working GraphQL API available. Please ensure Magento is running and accessible.');
    }

    /**
     * Execute real GraphQL query via HTTP
     */
    protected function executeRealGraphQLQuery(
        string $query,
        array  $variables = [],
        string $operationName = '',
        array  $headers = []
    ): array
    {
        // Prepare the payload
        $payload = [
            'query' => $query
        ];

        if (!empty($variables)) {
            $payload['variables'] = $variables;
        }

        if (!empty($operationName)) {
            $payload['operationName'] = $operationName;
        }

        // Prepare headers
        $defaultHeaders = [
            'Content-Type: application/json',
            'Store: default'
        ];

        $allHeaders = array_merge($defaultHeaders, $headers);

        // Make the HTTP request
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->workingGraphqlUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => $allHeaders,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception("GraphQL request failed: $error");
        }

        if ($httpCode !== 200) {
            throw new \Exception("GraphQL request failed with HTTP code: $httpCode");
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON response: " . json_last_error_msg());
        }

        // Check for GraphQL errors
        if (isset($decoded['errors']) && !empty($decoded['errors'])) {
            $errorMessage = 'GraphQL errors: ';
            foreach ($decoded['errors'] as $error) {
                $errorMessage .= $error['message'] . '; ';
            }
            throw new \Exception($errorMessage);
        }

        return $decoded['data'] ?? [];
    }


    /**
     * Set guest email on cart
     */
    protected function setGuestEmail(string $maskedCartId): array
    {
        $mutation = '
            mutation setGuestEmailOnCart($cart_id: String!, $email: String!) {
                setGuestEmailOnCart(input: {
                    cart_id: $cart_id
                    email: $email
                }) {
    cart {
      email
    }
  }
}
        ';

        return $this->graphQlMutation($mutation, [
            'cart_id' => $maskedCartId,
            'email' => 'test@buckaroo.com'
        ]);
    }

    /**
     * Set shipping address on cart
     */
    protected function setShippingAddress(string $maskedCartId): array
    {
        $mutation = '
            mutation setShippingAddressesOnCart($cart_id: String!, $shipping_addresses: [ShippingAddressInput!]!) {
                setShippingAddressesOnCart(input: {
                    cart_id: $cart_id
                    shipping_addresses: $shipping_addresses
                }) {
    cart {
      shipping_addresses {
        firstname
        lastname
        company
        street
        city
        postcode
        country {
          code
                            }
                            telephone
                            available_shipping_methods {
                                carrier_code
                                method_code
                                carrier_title
                                method_title
                                amount {
                                    value
                                    currency
                                }
        }
      }
    }
  }
}
        ';

        return $this->graphQlMutation($mutation, [
            'cart_id' => $maskedCartId,
            'shipping_addresses' => [
                [
                    'address' => [
                        'firstname' => 'Test',
                        'lastname' => 'User',
                        'company' => '', // Empty company for B2C transactions (Person category)
                        'street' => ['Hoofdstraat', '1'],
                        'city' => 'Utrecht',
                        'postcode' => '3511 CA',
                        'country_code' => 'NL',
                        'telephone' => '0307115000',
                        'save_in_address_book' => false
                    ]
                ]
            ]
        ]);
    }

    /**
     * Set billing address on cart
     */
    protected function setBillingAddress(string $maskedCartId): array
    {
        $mutation = '
            mutation setBillingAddressOnCart($cart_id: String!, $billing_address: BillingAddressInput!) {
                setBillingAddressOnCart(input: {
                    cart_id: $cart_id
                    billing_address: $billing_address
                }) {
    cart {
      billing_address {
        firstname
        lastname
        company
        street
        city
        postcode
        country {
          code
                            }
                            telephone
                        }
                    }
                }
            }
        ';

        return $this->graphQlMutation($mutation, [
            'cart_id' => $maskedCartId,
            'billing_address' => [
                'address' => [
                    'firstname' => 'Test',
                    'lastname' => 'User',
                    'company' => '', // Empty company for B2C transactions (Person category)
                    'street' => ['Hoofdstraat', '1'],
                    'city' => 'Utrecht',
                    'postcode' => '3511 CA',
                    'country_code' => 'NL',
                    'telephone' => '0307115000'
                ]
            ]
        ]);
    }

    /**
     * Set shipping method on cart
     */
    protected function setShippingMethod(string $maskedCartId): array
    {
        // First get available shipping methods
        $shippingQuery = '
            query cart($cart_id: String!) {
                cart(cart_id: $cart_id) {
                    shipping_addresses {
                        available_shipping_methods {
                            carrier_code
                            method_code
                            carrier_title
                            method_title
                        }
                    }
                }
            }
        ';

        $shippingResult = $this->graphQlQuery($shippingQuery, ['cart_id' => $maskedCartId]);
        $availableMethods = $shippingResult['cart']['shipping_addresses'][0]['available_shipping_methods'] ?? [];

        if (empty($availableMethods)) {
            throw new \Exception('No shipping methods available');
        }

        // Use the first available shipping method
        $method = $availableMethods[0];

        $mutation = '
            mutation setShippingMethodsOnCart($cart_id: String!, $shipping_methods: [ShippingMethodInput!]!) {
                setShippingMethodsOnCart(input: {
                    cart_id: $cart_id
                    shipping_methods: $shipping_methods
    }) {
        cart {
            shipping_addresses {
                selected_shipping_method {
                    carrier_code
                    method_code
                    carrier_title
                    method_title
                                amount {
                                    value
                                    currency
                                }
                            }
                        }
                        prices {
                            grand_total {
                                value
                                currency
                            }
                        }
                    }
                }
            }
        ';

        return $this->graphQlMutation($mutation, [
            'cart_id' => $maskedCartId,
            'shipping_methods' => [
                [
                    'carrier_code' => $method['carrier_code'],
                    'method_code' => $method['method_code']
                ]
            ]
        ]);
    }

    /**
     * Set Buckaroo return URL
     */
    protected function setBuckarooReturnUrl(string $maskedCartId, string $returnUrl = 'https://test.local/success'): array
    {
        $mutation = '
            mutation setBuckarooReturnUrl($cart_id: String!, $return_url: String!) {
                setBuckarooReturnUrl(input: {
                    cart_id: $cart_id
                    return_url: $return_url
                }) {
                    success
                }
            }
        ';

        return $this->graphQlMutation($mutation, [
            'cart_id' => $maskedCartId,
            'return_url' => $returnUrl
        ]);
    }

    /**
     * Validate Buckaroo payment response
     */
    protected function validateBuckarooPaymentResponse(array $response, string $description): void
    {
        $this->log("✅ Step 7: Response Validation");
        $this->log("🔍 Validating payment response structure", 'DEBUG');

        // Extract placeOrder data from real GraphQL API response
        $this->assertArrayHasKey('placeOrder', $response, 'placeOrder key should exist in response');
        $placeOrderData = $response['placeOrder'];
        $this->log("✅ Real GraphQL API response received", 'DEBUG');

        // Check if order exists
        $this->assertArrayHasKey('order', $placeOrderData, 'Order key should exist in placeOrder response');
        $this->log("✅ Order key found", 'DEBUG');

        $order = $placeOrderData['order'];

        // Add null check for order
        if ($order === null) {
            $this->log("❌ Order data is null - possible payment method configuration issue", 'ERROR', [
                'placeOrderData' => $placeOrderData
            ]);
            $this->fail('Order data is null - this usually indicates a payment method configuration issue or the payment method is not available');
        }

        // Ensure order is an array
        $this->assertIsArray($order, 'Order should be an array');

        // Validate order number
        $this->assertArrayHasKey('order_number', $order, 'Order number should be present');
        $this->assertNotEmpty($order['order_number'], 'Order number should not be empty');
        $this->log("✅ Order number generated", 'SUCCESS', ['order_number' => $order['order_number']]);

        // Check if Buckaroo additional data is present (not all payment methods provide this)
        if (array_key_exists('buckaroo_additional', $order) && !empty($order['buckaroo_additional'])) {
            $this->log("✅ Buckaroo additional data found", 'DEBUG');
            $buckarooData = $order['buckaroo_additional'];

            // Validate redirect URL if present
            if (array_key_exists('redirect', $buckarooData) && !empty($buckarooData['redirect'])) {
                $this->assertStringContainsString('buckaroo', strtolower($buckarooData['redirect']), 'Redirect URL should contain Buckaroo domain');
                $this->log("✅ Buckaroo redirect URL validated", 'SUCCESS', ['redirect_url' => $buckarooData['redirect']]);
            } else {
                $this->log("⚠️ No redirect URL in Buckaroo data (may be direct payment method)", 'INFO');
            }

            // Validate transaction ID if present
            if (array_key_exists('BRQ_TRANSACTIONS', $buckarooData) && !empty($buckarooData['BRQ_TRANSACTIONS'])) {
                $this->assertNotEmpty($buckarooData['BRQ_TRANSACTIONS'], 'Transaction ID should not be empty');
                $this->log("✅ Buckaroo transaction ID validated", 'SUCCESS', ['transaction_id' => $buckarooData['BRQ_TRANSACTIONS']]);
            }
        } else {
            // Some payment methods don't use Buckaroo redirects (e.g., direct payments, disabled methods)
            $this->log("⚠️ No Buckaroo additional data (payment method may not require redirect or may be unavailable)", 'INFO');

            // Still verify we have a valid order
            $this->assertNotEmpty($order['order_number'], 'Order should still be created even without Buckaroo data');
            $this->log("✅ Order created successfully without Buckaroo redirect", 'SUCCESS', ['payment_method' => $description]);
        }
    }

    /**
     * Extract Buckaroo redirect URL from order response
     */
    protected function extractRedirectUrl(array $response): string
    {
        $this->assertArrayHasKey('placeOrder', $response, 'placeOrder key should exist in response');
        $placeOrderData = $response['placeOrder'];

        $this->assertArrayHasKey('order', $placeOrderData, 'Order key should exist in placeOrder response');
        $order = $placeOrderData['order'];

        // Check if Buckaroo additional data exists and has redirect URL
        if (!array_key_exists('buckaroo_additional', $order) || empty($order['buckaroo_additional'])) {
            return ''; // No redirect URL available for this payment method
        }

        $buckarooData = $order['buckaroo_additional'];

        if (!array_key_exists('redirect', $buckarooData) || empty($buckarooData['redirect'])) {
            return ''; // No redirect URL in Buckaroo data
        }

        $redirectUrl = $buckarooData['redirect'];

        $this->assertNotEmpty($redirectUrl, 'Redirect URL should not be empty');
        $this->assertStringContainsString('buckaroo', strtolower($redirectUrl), 'Redirect URL should contain Buckaroo domain');

        return $redirectUrl;
    }

    /**
     * Create complete checkout flow
     */
    protected function createCompleteCheckoutFlow(string $paymentCode, array $paymentData, string $productSku = ''): array
    {
        // Step 1: Create cart and add product
        $this->log("📋 Step 1: Cart & Product Setup");
        $maskedCartId = $this->createEmptyCart();
        $this->addProductToCart($maskedCartId, $productSku);

        // Step 2: Set customer information
        $this->log("👤 Step 2: Customer Information");
        $this->setGuestEmail($maskedCartId);

        // Step 3: Set addresses
        $this->log("🏠 Step 3: Address Configuration");
        $this->setShippingAddress($maskedCartId);
        $this->setBillingAddress($maskedCartId);

        // Step 4: Set shipping method
        $this->log("🚚 Step 4: Shipping Method");
        $this->setShippingMethod($maskedCartId);

        // Step 5: Set payment method
        $this->log("💳 Step 5: Payment Method Configuration");
        $this->setPaymentMethodWithData($maskedCartId, $paymentCode, $paymentData);

        // Step 6: Place order
        $this->log("🎯 Step 6: Order Placement");
        return $this->placeOrder($maskedCartId);
    }

    /**
     * Create empty cart
     */
    protected function createEmptyCart(): string
    {
        $mutation = 'mutation { createEmptyCart }';
        $result = $this->graphQlMutation($mutation);

        // Extract cart ID from real GraphQL API response
        if (isset($result['createEmptyCart'])) {
            $cartId = $result['createEmptyCart'];
        } else {
            throw new \Exception('Invalid createEmptyCart response format: ' . json_encode($result));
        }

        $this->log("🛒 Empty cart created", 'INFO', ['cart_id' => $cartId]);
        return $cartId;
    }

    /**
     * Add product to cart
     */
    protected function addProductToCart(string $maskedCartId, string $sku = ''): void
    {
        if (empty($sku)) {
            $sku = $this->defaultProductSku;
        }

        $mutation = '
            mutation addSimpleProductsToCart($cart_id: String!, $cart_items: [SimpleProductCartItemInput!]!) {
                addSimpleProductsToCart(input: {
                    cart_id: $cart_id
                    cart_items: $cart_items
                }) {
                    cart {
                        total_quantity
                        items {
                            id
                            quantity
                            product {
                                sku
                                name
                            }
                        }
                        prices {
                            subtotal_excluding_tax {
                                value
                                currency
                            }
                        }
                    }
                }
            }
        ';

        $this->graphQlMutation($mutation, [
            'cart_id' => $maskedCartId,
            'cart_items' => [
                [
                    'data' => [
                        'quantity' => 1,
                        'sku' => $sku
                    ]
                ]
            ]
        ]);
    }

    /**
     * Set payment method with additional data
     */
    protected function setPaymentMethodWithData(string $maskedCartId, string $paymentCode, array $paymentData): array
    {
        $mutation = '
            mutation setPaymentMethodOnCart($cart_id: String!, $payment_method: PaymentMethodInput!) {
                setPaymentMethodOnCart(input: {
                    cart_id: $cart_id
                    payment_method: $payment_method
                }) {
                    cart {
                        selected_payment_method {
                            code
                            title
                        }
                    }
                }
            }
        ';

        $paymentMethodInput = [
            'code' => $paymentCode
        ];

        // Add buckaroo_additional data if provided
        if (!empty($paymentData)) {
            $paymentMethodInput['buckaroo_additional'] = [
                $paymentCode => $paymentData
            ];
        }

        return $this->graphQlMutation($mutation, [
            'cart_id' => $maskedCartId,
            'payment_method' => $paymentMethodInput
        ]);
    }

    /**
     * Place order
     */
    protected function placeOrder(string $maskedCartId): array
    {
        $mutation = '
            mutation placeOrder($cart_id: String!) {
                placeOrder(input: { cart_id: $cart_id }) {
                    order {
                        order_number
                        buckaroo_additional {
                            redirect
                            transaction_id
                        }
                    }
                }
            }
        ';

        return $this->graphQlMutation($mutation, [
            'cart_id' => $maskedCartId
        ]);
    }

    /**
     * Get Buckaroo transaction status
     */
    protected function getBuckarooTransactionStatus(string $transactionId): array
    {
        $query = '
            query buckarooPaymentTransactionStatus($transaction_id: String!) {
                buckarooPaymentTransactionStatus(input: {
                    transaction_id: $transaction_id
                }) {
                    payment_status
                    status_code
                }
            }
        ';

        return $this->graphQlQuery($query, [
            'transaction_id' => $transactionId
        ]);
    }
}
