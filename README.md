<p align="center">
  <img src="https://www.buckaroo.nl/media/2975/m2_icon.jpg" width="150px" position="center">
</p>


# Buckaroo Magento 2 GraphQL extension

### Requirements
To use the plugin you must use: 
- Magento Open Source version 2.3.x & 2.4.x
- Buckaroo Magento 2 Payment module 1.39.0 or greater 

## Installation
  - Install the module composer by running `composer require buckaroo/module-magento2graphql`
  - enable the module by running `php bin/magento module:enable Buckaroo_Magento2Graphql`
  - apply database updates by running `php bin/magento setup:upgrade`
  - Flush the cache by running `php bin/magento cache:flush`

## Usage

### Example requests

- Get the available payments methods with additional data for gateways:
```graphql
query {
    cart(cart_id: "{ CART_ID }") {
        available_payment_methods {
            code
            title
            buckaroo_additional {
                key
                values {
                    name
                    code
                    img
                }
                value
            }
        }
    }
}
```

- Place order request example: In order to place a order you will need to the following 3 steps:
  - Set the payment method on the card with the required additional parameters using the default `setPaymentMethodOnCart` and the `buckaroo_additional` property
  - Set the return url using our custom migration `setBuckarooReturnUrl` required in order for the payment engine to redirect back to your application after the payment was completed/canceled/failed
  - Finally execute the the default `placeOrder` that will return a redirect url for the payment engine to complete the payment

For Ideal we have the following example:
```graphql
  mutation doBuckarooPayment(
  $cartId: String!
  $returnUrl: String!
  $methodCode: String!
) {
  setPaymentMethodOnCart(
    input: {
      cart_id: $cartId
      payment_method: {
        code: $methodCode
        buckaroo_additional: { buckaroo_magento2_ideal: { issuer: "ABNANL2A" } }
      }
    }
  ) {
    cart {
      items {
        product {
          name
          sku
        }
      }
    }
  }
  setBuckarooReturnUrl(input: { return_url: $returnUrl, cart_id: $cartId }) {
    success
  }
  placeOrder(input: { cart_id: $cartId }) {
    order {
      order_number
      buckaroo_additional {
        redirect
        transaction_id
        data {
          key
          value
        }
      }
    }
  }
}

```
After this migration is performed you will need to store the buckaroo `transaction_id` and redirect the user to complete the payment

In order to get the payment status after the user is redirected back we will use our custom migration `buckarooPaymentTransactionStatus` that will need the stored `transaction_id`

```graphql
mutation buckarooPaymentTransactionStatus(input: { transaction_id: "E397CF4C24E64AA299F45246F9906F45" }) {
  payment_status,
  status_code
}
```
### Additional information
For more information on Buckaroo GraphQL please visit:
https://support.buckaroo.nl/categorieen/plugins/magento-2

## Contribute
See [Contribution Guidelines](CONTRIBUTING.md)

## Support:

https://support.buckaroo.nl/contact
