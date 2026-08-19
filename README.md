<p align="center">
  <a href="https://www.buckaroo.nl">
    <img src="https://raw.githubusercontent.com/buckaroo-it/Media/main/Buckaroo/README.md%20Headers/buckaroo-magento2-graphql-header-rounded.png" alt="Buckaroo — GraphQL for Magento 2" width="100%">
  </a>
</p>

<h1 align="center">Buckaroo GraphQL module for Magento 2</h1>

<p align="center">
  <a href="#about">About</a> &middot;
  <a href="#requirements">Requirements</a> &middot;
  <a href="#installation">Installation</a> &middot;
  <a href="#upgrade">Upgrade</a> &middot;
  <a href="#usage">Usage</a> &middot;
  <a href="#support">Support</a> &middot;
  <a href="#contribute">Contribute</a>
</p>

---

## About

GraphQL is a query language for APIs. It describes the data your API exposes, lets a client ask for exactly what it needs and nothing more, and makes it easier to evolve the API over time.

This module exposes Buckaroo's payment methods through Magento's GraphQL API, so a headless or single-page storefront can list the available methods, set one on the cart, place the order and check the payment status.

> [!IMPORTANT]
> The [Buckaroo Magento 2 plugin](https://github.com/buckaroo-it/Magento2) is required. This module extends it and cannot be used on its own — install and configure the payments plugin first.

It is also a prerequisite for the [Hyvä React Checkout module](https://github.com/buckaroo-it/Magento2_Hyva), which relies on these GraphQL endpoints.

[Full module documentation on docs.buckaroo.io](https://docs.buckaroo.io/docs/magento-2-new-additional-modules-graphql-module)

---

## Requirements

| Requirement | Supported versions |
|---|---|
| [Buckaroo Magento 2 plugin](https://github.com/buckaroo-it/Magento2) | 1.52.1 or higher |

Magento, PHP and Composer requirements come from the [main plugin](https://github.com/buckaroo-it/Magento2). You also need a Buckaroo account — don't have one yet? [Request an account](https://www.buckaroo.nl/start).

---

## Installation

Run the following commands from your Magento 2 root folder:

```bash
composer require buckaroo/magento2graphql
php bin/magento module:enable Buckaroo_Magento2Graphql
php bin/magento setup:upgrade
php bin/magento cache:flush
```

---

## Upgrade

```bash
composer update buckaroo/magento2graphql
php bin/magento setup:upgrade
php bin/magento cache:flush
```

> [!TIP]
> Always test an upgrade on a staging environment first.

---

## Usage

Payment methods are enabled and configured in the main plugin, under **Stores → Configuration → Sales → Buckaroo** in the Magento admin. This module only exposes them; it has no settings of its own.

### Listing the available payment methods

Each method comes back with its Buckaroo-specific extra data under `buckaroo_additional`:

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

### Placing an order

Placing an order takes three steps, which can be combined into one mutation:

1. Set the payment method on the cart with the standard `setPaymentMethodOnCart`, passing any method-specific fields under `buckaroo_additional`.
2. Set the return URL with `setBuckarooReturnUrl`, so the payment engine knows where to send the customer after the payment completes, is cancelled or fails.
3. Call the standard `placeOrder`, which returns the redirect URL.

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
        buckaroo_additional: { buckaroo_magento2_ideal: {} }
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

Store the returned `transaction_id`, then redirect the customer to the `redirect` URL to complete the payment.

> [!NOTE]
> iDEAL no longer requires an issuer, so `buckaroo_magento2_ideal` can be left empty. The customer picks their bank on the redirect page.

<details>
<summary>SEPA Direct Debit example</summary>

SEPA Direct Debit needs the mandate details passed as additional fields:

```graphql
mutation doSepaPayment($cartId: String!, $returnUrl: String!) {
  setPaymentMethodOnCart(
    input: {
      cart_id: $cartId
      payment_method: {
        code: "buckaroo_magento2_sepadirectdebit"
        buckaroo_additional: {
          buckaroo_magento2_sepadirectdebit: {
            customer_iban: "NL13TEST0123456789"
            customer_bic: "TESTNL2A"
            customer_account_name: "Test Account Holder"
          }
        }
      }
    }
  ) {
    cart {
      selected_payment_method {
        code
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
        transaction_id
      }
    }
  }
}
```

</details>

### Retrieving the payment status

Once the customer is redirected back, query the status with the stored `transaction_id`:

```graphql
mutation {
  buckarooPaymentTransactionStatus(input: { transaction_id: "E397CF4C24E64AA299F45246F9906F45" }) {
    payment_status
    status_code
  }
}
```

For dynamic values, use variables instead:

```graphql
mutation checkPaymentStatus($input: BuckarooPaymentTransactionStatusInput!) {
  buckarooPaymentTransactionStatus(input: $input) {
    payment_status
    status_code
  }
}
```

```json
{
  "input": {
    "transaction_id": "E397CF4C24E64AA299F45246F9906F45"
  }
}
```

---

## Support

Having trouble? Work through this list before reaching out:

1. Confirm the [main plugin](https://github.com/buckaroo-it/Magento2) is installed and active, and that the method works in a standard Magento checkout.
2. Confirm you are on the [latest release](https://github.com/buckaroo-it/Magento2_GraphQL/releases) of both this module and the main plugin.
3. Check that the method code and the `buckaroo_additional` key match, for example `buckaroo_magento2_ideal` for code `buckaroo_magento2_ideal`.
4. Flush the cache after installing or upgrading.

Still stuck? Contact us and include your Magento version, main plugin version, this module's version, the query or mutation you sent and the response you received.

- **Bug reports and feature requests:** [open an issue](https://github.com/buckaroo-it/Magento2_GraphQL/issues)
- **Technical support:** [support@buckaroo.nl](mailto:support@buckaroo.nl)
- **Phone:** +31 (0)30 711 50 50
- **Gateway status:** [status.buckaroo.io](https://status.buckaroo.io/)

---

## Contribute

We really appreciate it when developers help improve the Buckaroo plugins. Please read our [Contribution Guidelines](https://github.com/buckaroo-it/Magento2_GraphQL/blob/main/CONTRIBUTING.md) before opening a pull request, and target the `main` branch.

Found a security issue? Please report it privately to [support@buckaroo.nl](mailto:support@buckaroo.nl) instead of opening a public issue.

---

## Versioning

We follow semantic versioning (`MAJOR.MINOR.PATCH`):

- **MAJOR** — breaking changes that require additional testing and caution.
- **MINOR** — new functionality with limited impact.
- **PATCH** — bug fixes and hotfixes only.

---

## License

This module is open source software licensed under the [MIT license](https://github.com/buckaroo-it/Magento2_GraphQL/blob/main/LICENSE).

---

<p align="center">
  <sub>Made with care by <a href="https://www.buckaroo.nl">Buckaroo</a>.<br>
  This document is subject to change; typos and language errors are possible.</sub>
</p>
