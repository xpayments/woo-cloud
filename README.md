# X-Payments Cloud connector for WooCommerce

This extension connects your WooCommerce store with X-Payments Cloud - a PSD2/SCA ready PCI Level 1 certified credit card processing app which allows you to store customers' credit card information and still be compliant with PCI security mandates.

The credit card form is embedded right into the checkout page, so your customers don't leave your site to complete an order. Card details are entered in an iframe served by X-Payments Cloud and never reach your WooCommerce server.

The extension is in beta.

### Requirements

- WordPress with WooCommerce
- PHP 7.1 or newer, including PHP 8.x, with the curl, hash, openssl and json extensions
- HTTPS on the store: shoppers return from 3-D Secure to an `https://` URL

### Installation

1. Copy the `woocommerce-gateway-x-payments-cloud` directory into `wp-content/plugins/`.
2. Activate **WooCommerce X-Payments Cloud connector** on the **Plugins** page.

The plugin bundles the [X-Payments Cloud PHP SDK](https://github.com/xpayments/cloud-sdk-php) in `lib/`, so no Composer step is needed.

### Configuration

First, create the X-Payments Cloud account:
 - Navigate to **WooCommerce -> Settings -> Payments -> All payment methods**
 - Click the **Manage** button next to **X-Payments Cloud**
 - Start the signup process to create an X-Payments Cloud account and follow the wizard instructions
 - At the end you will be prompted to set a password. Then complete the 2-step user authentication setup for your account

X-Payments Cloud is now available at checkout in demo mode. Don't forget to enable it.

To start accepting real payments, go back to the X-Payments Cloud payment method configuration and:
  - Select the necessary payment gateway from the **Add payment configuration** list
  - Enter your gateway credentials and adjust the settings specific to this payment gateway

Refunds can be issued from the WooCommerce order page.

See also: [Using X-Payments Cloud with WooCommerce](https://www.x-payments.com/help/XP_Cloud:Using_X-Payments_Cloud_with_WooCommerce).

### Supported payment gateways
X-Payments Cloud supports more than 60 payment gateway integrations: ANZ eGate, American Express Web-Services API Integration, Authorize.Net, Bambora (Beanstream), Beanstream (legacy API), Bendigo Bank, BillriantPay, BluePay, BlueSnap Payment API (XML), Braintree, BluePay Canada (Caledon), Cardinal Commerce Centinel, Chase Paymentech, CommWeb - Commonwealth Bank, BAC Credomatic, CyberSource - SOAP Toolkit API, X-Payments Demo Pay, X-Payments Demo Pay 3-D Secure, DIBS, DirectOne - Direct Interface, eProcessing Network - Transparent Database Engine, SecurePay Australia, Moneris eSELECTplus, Elavon (Realex API), ePDQ MPI XML (Phased out), eWAY Rapid - Direct Connection, eWay Realtime Payments XML, Sparrow (5th Dimension Gateway), First Data Payeezy Gateway (ex- Global Gateway e4), Global Iris, Global Payments, GoEmerchant - XML Gateway API, HeidelPay, Innovative Gateway, iTransact XML, Payment XP (Meritus) Web Host, NAB - National Australia Bank, NMI (Network Merchants Inc.), Netbilling - Direct Mode, Netevia, Ingenico ePayments (Ogone e-Commerce), PayGate South Africa, Payflow Pro, PayPal REST API, PayPal Payments Pro (PayPal API), PayPal Payments Pro (Payflow API), PSiGate XML API, QuantumGateway - XML Requester, Intuit QuickBooks Payments, QuickPay, Worldpay Corporate Gateway - Direct Model, Global Payments (ex. Realex), Opayo Direct (ex. Sage Pay Go - Direct Interface), Paya (ex. Sage Payments US), Simplify Commerce by MasterCard, SkipJack, Suncorp, TranSafe, powered by Monetra, 2Checkout, USA ePay - Transaction Gateway API, Elavon Converge (ex VirtualMerchant), WebXpress, Worldpay Total US, Worldpay US (Lynk Systems).

### Supported fraud-screening services
 - Kount
 - NoFraud
 - Signifyd

### Reference
 - [X-Payments Cloud API](https://xpayments.stoplight.io/docs/server-side-api/spsxnj22ewcd7-x-payments-cloud-api-overview)
 - [X-Payments Cloud PHP SDK](https://github.com/xpayments/cloud-sdk-php)
 - [X-Payments development docs](https://support.x-cart.com/en/articles/5842415-x-payments-development-docs)
 - [X-Payments Cloud user manual](https://support.x-cart.com/en/collections/3159781-x-payments-cloud)

### License

GPL-2.0-or-later. See [LICENSE](LICENSE). The bundled PHP SDK in `lib/` is subject to the [X-Cart License Agreement](https://www.x-cart.com/license-agreement.html).

Copyright (c) 2019-present X-Cart Holdings LLC.

### Support
If you have any questions, please [contact us](https://www.x-payments.com/contact-us).
