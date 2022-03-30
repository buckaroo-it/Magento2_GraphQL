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
  - apply database updates by running `php bin/magento setup:upgrade`\*
  - Flush the cache by running `php bin/magento cache:flush`

### Configuration
For payments that have a redirect the user will be redirected back to your PWA/SPA app with the state of the payment request, the app url can be setup in the buckaroo setting panel `Stores \ Configuration \ Sales(tab) \ Buckaroo \ GraphQL (Section)` 

The settings page should contain the following:

 - A base URL to your PWA/SPA app (ex: https://pwa.example.com)

 - A Payment processed route (relative to your base url) where the user will be redirected with the outcome of payment process (ex: /complete-payment)

## Usage
To create an order using GraphQL, please take a look at the [Magento manual](https://devdocs.magento.com/guides/v2.4/graphql/tutorials/checkout/index.html)

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
- setPaymentMethodOnCart query example (for payment `PAYMENT_ID` of `buckaroo_magento2_ideal`):
```graphql
mutation {
    setPaymentMethodOnCart(input: {
        cart_id: "{ CART_ID }"
        payment_method: {
            code: "{ PAYMENT_ID }"
            buckaroo_additional: {
                { PAYMENT_ID }: {
                    issuer: "ABNANL2A"
                }
            }
        }
    }) {
        cart {
            selected_payment_method {
                code
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
}
```
- Place order request example. After placing the order, you will receive a `redirect` url for payments that require additional steps to complete, other payments don't require additional steps and are completed after this mutation (`redirect` will be `null`):
```graphql
mutation {
    placeOrder(input: {cart_id: "{ CART_ID }"}) {
        order {
            order_number
            buckaroo_additional {
                redirect
                data {
                    key
                    value
                }
            }
        }
    }
}
```
Note: some payment methods will offer additional `data` that allows the user to complete the payment using a QrCode /Mobile Banking app (ex: Payconiq, Bancontact/Mister Cash) check the [Buckaroo documentation](https://dev.buckaroo.nl/PaymentMethods/) for info.

Note: For payments that require a redirect:
After the user completes/cancels the payment in the payment provider page he will be redirected back to your app with the status of the payment.

A message, the order increment id and other relevant data will be provided as query parameters.

Example:

```https://pwa.example.com/complete-payment?route=checkout&order_number=000000001&message_type=error&message=Unfortunately+an+error+occurred+while+processing+your+payment.+Please+try+again.+If+this+error+persists%2C+please+choose+a+different+payment+method.&bk_e=1```

Using this data you can notify user about the status of the order, redirect the user back to the cart/success page

### iDIN age verification
iDIN age verification should be done at checkout before the user selects the payment methods

To get the current state of iDIN we should do the following query
```graphql
{
  getBuckarooIdin(cart_id: "{ CART_ID }") {
    active
    issuers {
      code
      name
    }
    verified
  }
}
```
Where fields: 

- `active` - Determine if iDIN is active or not
- `issuers` - List of issuers available for iDIN verification
- `verified` - Determine if age is verified for this 
customer/session

If the customer/session is not verified then we can do a iDIN age verification request using the `verifyBuckarooIdin` migration

```graphql
mutation {
  verifyBuckarooIdin(input: { 
      issuer: "BANKNL2Y"
      cart_id: "{ CART_ID }"
    }) {
    redirect
  }
}
```
If the request is successful the customer should be redirected to the verification provider where he will complete the verification process after which he will be returned back to cart to complete the order
### Additional information
For more information on Buckaroo GraphQL please visit:
https://support.buckaroo.nl/categorieen/plugins/magento-2

## Contribute
See [Contribution Guidelines](CONTRIBUTING.md)

## Support:

https://support.buckaroo.nl/contact
