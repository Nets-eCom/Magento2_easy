# NETS A/S - Magento 2 Payment Module
============================================

|Module | Nets Easy Payment Module for Magento 2
|------|----------
|Author | `Nets eCom`
|Prefix | `EASY-M2`
|Shop Version | `2.4+`
|Version | `1.5.7`
|Documentation Guide | https://developers.nets.eu/nets-easy/en-EU/docs/nets-easy-for-magento/nets-easy-for-magento-magento-2/
|Github | https://github.com/Nets-eCom/Magento2_easy
|License | `MIT License`

## INSTALLATION

### Download / Installation
* Method 1
1. Download the latest Nets Easy module from GitHub: https://github.com/Nets-eCom/Magento2_easy/archive/refs/heads/master.zip 
2. Unzip the file and upload the content to your Magento site via FTP, into the directory /app/code/Dibs/EasyCheckout. Please create the folder /Dibs/EasyCheckout first time you install the plugin.

* Method 2
Another option is to install the module via composer at your magento site by running the following commands:
	$ composer config repositories.dibs_easycheckout vcs git@github.com:/Nets-eCom/Magento2_easy.git
	$ composer require --prefer-source dibs/easycheckout:*

NOTE : It is strongly recommended to have cleanup of Pending Payment orders active in Sales > Orders, Orders Cron Settings > Pending Payment Order Lifetime (minutes)

### Enable the module
Enable the Nets Easy module in Magento by running the following commands:

	$ php bin/magento module:enable --clear-static-content Dibs_EasyCheckout
	$ php bin/magento setup:upgrade

### Configuration
1. To configure and setup the plugin navigate to : Stores > Configuration > Nets > Easy Checkout

* Settings Description
1. Login to your Nets Easy account. Keys can be found in Company > Integration :
2. Payment Environment. Select between Test/Live transactions. Live mode requires an approved account. Testcard information can be found here : https://portal.dibspayment.eu/
3. Checkout Flow. Redirect / Embedded. Select between 2 checkout types. Redirect - Nets Hosted loads a new payment page. Embedded checkout inserts the payment window directly on the checkout page.
4. Enable auto-capture. This function allows you to instantly charge a payment straight after the order is placed.
   NOTE. Capturing a payment before shipment of the order might be liable to restrictions based upon legislations set in your country. Misuse can result in your Easy account being forfeit.
   
For more details, please find below URL :-
https://developers.nets.eu/nets-easy/en-EU/docs/nets-easy-for-magento/nets-easy-for-magento-magento-2/#build-configuration

### Operations
* Cancel / Capture / Refund
1. Navigate to admin > Orders > Overview. Press on Order number to access order details.
2. Choose your desired action.
3. All transactions by Nets are accessible in our portal : https://portal.dibspayment.eu/login

### Troubleshooting
* Nets payment plugin is not visible as a payment method
- Temporarily switch to Magento standard template. Custom templates might need addtional changes to ensure correct display. Consult with your webdesigner / developer.

* Nets payment window is blank
- Ensure your keys in Nets plugin Settings are correct and with no additional blank spaces.
- Temporarily deactivate third party plugins that might effect the functionality of the Nets plugin.
- Check if there is any temporary technical inconsistencies : https://nets.eu/Pages/operational-status.aspx

* Payments in live mode don't work
- Ensure you have an approved Live Easy account for production.
- Ensure your Live Easy account is approved for payments with selected currency.
- Ensure payment method data is correct and supported by your Nets Easy agreement.

* Log file
- Info, debugging, and error messages will be logged in var/log/dibs_easycheckout.log

### Contact
* Nets customer service
- Nets Easy provides support for both test and live Easy accounts. Contact information can be found here : https://nets.eu/en/payments/customerservice/

** CREATE YOUR FREE NETS EASY TEST ACCOUNT HERE : https://portal.dibspayment.eu/registration **
