# Nexi Checkout Payment Module for Adobe Commerce

Integrate Nexi Checkout with your Magento 2 / Adobe Commerce store. The module supports embedded and hosted checkout, payment management in Magento Admin, and payment status updates through webhooks.

## Requirements

- PHP `^8.1` with the cURL extension.
- Magento Framework.
- Nexi Checkout account and the corresponding test or live API keys are required to process payments.

Use a PHP version supported by your Magento installation.

## Key Features
### Shop Features

- Seamless checkout experience with multiple payment options.
- Checkout for guests and registered customers.
- Embedded checkout for an optimized user experience. Users are not redirected outside Adobe Commerce.
- Optional payment method splitting to display individual payment options at checkout.
- A smart mix of payment methods to suit all preferences.

### Administration Features

- Quick setup and flexible configuration.
- Automatic capture or subsequent capture through Magento's payment workflow.
- Intuitive order management with synchronized payment status via webhooks.
- Full and partial captures and refunds, plus cancellation of reserved payments.
- Webhook handling for reservation, charge, refund, and cancellation events.
- Compatible with discounts, tax (VAT) and shipping variants.

## Installation
Follow the steps below to install and enable the Nexi Checkout module in Magento 2.

### Step 1: Install the module
You have two options when installing the Checkout module.

#### Alternative 1: Using composer
** It is strongly recommended to have cleanup of Pending Payment orders active in Sales > Orders, Orders Cron Settings > Pending Payment Order Lifetime (minutes) **

Install the module via composer at your magento site by running the following commands:

```sh
$ composer require --no-dev nexi-checkout/adobe-commerce-checkout
```

#### Alternative 2: Manual upload
Another option is to manually upload the file to your Magento site:

1. Download the latest Nexi Checkout module from GitHub: https://github.com/Nets-eCom/Magento2_easy/releases
2. Unzip the file and upload the content to your Magento site via FTP, into the directory /app/code/Nexi/Checkout. Please create the folder /Nexi/Checkout first time you install the plugin.

### Step 2: Enable the module
Enable the Nexi Checkout module in Magento by running the following commands:

```sh
$ php bin/magento module:enable --clear-static-content Nexi_Checkout 
$ php bin/magento setup:upgrade
```

The module is now installed and ready to be configured in magento administation panel.

For production mode, run dependency injection compilation and deploy static content as part of your normal deployment process:

```sh
$ bin/magento setup:di:compile
$ bin/magento setup:static-content:deploy
```

## Configuration

In Magento Admin, open **Stores → Configuration** then **Sales → Payment Methods → Nexi Checkout**.

1. Set **Enabled** to **Yes** and modify the checkout **Title** if needed.
2. Configure **Is Auto Capture** and **New Order Status** for your order workflow.
3. Select **Test** or **Live** under **Environment**.
4. Enter the corresponding credentials: **Secret Key** and **Checkout Key** for live payments and **Test Secret Key** and **Test Checkout Key** for testing payments.
5. Optionally enable **Payment Method splitting**, select the available methods, and drag them into the desired order.
6. Enter the **Webshop Terms and Conditions URL** and **Payment Terms and Conditions URL**.
7. Choose **Embedded Checkout** or **Hosted Checkout** under **Integration type**.
8. Save the configuration and use **Test Connection** to verify connectivity.

## Customer Service

For assistance with Nexi Checkout test and live accounts, visit [Nexi Checkout Support](https://developer.nexigroup.com/nexi-checkout/en-EU/support/).

## For Developers

### Documentation

See the [Checkout for Adobe Commerce documentation](https://developer.nexigroup.com/nexi-checkout/en-EU/docs/magento/checkout-magento-2-adobe-commerce-magento-2-adobe-commerce/) for integration guidance. Configuration details may differ between module versions.

### Setup and Testing

- [Use a webshop plugin](https://developer.nexigroup.com/nexi-checkout/en-EU/docs/use-a-webshop-plugin/)
- [Create a Checkout account](https://developer.nexigroup.com/nexi-checkout/en-EU/docs/create-a-checkout-portal-account/)
- [Test environment](https://developer.nexigroup.com/nexi-checkout/en-EU/docs/test-environment/)
- [Test card processing](https://developer.nexigroup.com/nexi-checkout/en-EU/docs/test-card-processing/)

### API

The integration uses the [Nexi Checkout API](https://developer.nexigroup.com/nexi-checkout/en-EU/api/) through the Nexi Checkout PHP Payment SDK.
