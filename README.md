<p align="center">
  <img src="https://www.buckaroo.nl/media/w0sdhkjd/magento2_graphql_icon.png" width="200px" position="center">
</p>

# Buckaroo Magento2 GraphQL extension
We created a additional GraphQL extension for our [Buckaroo Magento 2 Payments Plugin](https://github.com/buckaroo-it/Magento2).<br>
This plugin is needed for our [Hÿva React checkout extension](https://github.com/buckaroo-it/Magento2_Hyva).<br>

<b>What is GraphQL?</b><br>GraphQL is a query language for APIs and a runtime for fulfilling those queries with your existing data. GraphQL provides a complete and understandable description of the data in your API, gives clients the power to ask for exactly what they need and nothing more, makes it easier to evolve APIs over time, and enables powerful developer tools.

### Index
- [Installation](#installation)
- [Requirements](#requirements)
- [Additional information](#additional-information)
- [Contribute](#contribute)
- [Versioning](#versioning)
- [Additional information](#additional-information)
---


### Requirements
To use the plugin you must use: 
- Magento Open Source version 2.4.x
- Buckaroo Magento 2 Payment module 1.43.0 or higher.

## Installation
  - Install the module composer by running the following command: `composer require buckaroo/magento2graphql`
  - Enable the module by running the following command: `php bin/magento module:enable Buckaroo_Magento2Graphql`
  - Apply database updates by running the following command: `php bin/magento setup:upgrade`
  - Flush the cache by running the following command: `php bin/magento cache:flush`

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
  - Set the payment method on the cart with the required additional parameters using the default `setPaymentMethodOnCart` and the `buckaroo_additional` property
  - Set the return url using our custom migration `setBuckarooReturnUrl` required in order for the payment engine to redirect back to your application after the payment was completed/canceled/failed
  - Finally execute the the default `placeOrder` that will return a redirect url for the payment engine to complete the payment

For iDEAL we have the following example:
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

### Retrieve the payment status

In order to get the payment status after the user is redirected back we will use our custom migration `buckarooPaymentTransactionStatus` that will need the stored `transaction_id`

```graphql
mutation buckarooPaymentTransactionStatus(input: { transaction_id: "E397CF4C24E64AA299F45246F9906F45" }) {
  payment_status,
  status_code
}
```

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

For iDEAL we have the following example:
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
- **Support:** https://support.buckaroo.eu/contact
- **Contact:** [support@buckaroo.nl](mailto:support@buckaroo.nl) or [+31 (0)30 711 50 50](tel:+310307115050)

### Contribute
We really appreciate it when developers contribute to improve the Buckaroo plugins.
If you want to contribute as well, then please follow our [Contribution Guidelines](CONTRIBUTING.md).

### Versioning 
<p align="left">
  <img src="https://www.buckaroo.nl/media/3651/graphql_versioning.png" width="500px" position="center">
</p>

<b>Please note:</b><br>
This file has been prepared with the greatest possible care and is subject to language and/or spelling errors.
