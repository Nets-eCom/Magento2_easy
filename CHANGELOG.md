# NETS A/S - Magento 2 Payment Module
============================================

|Module | Nets Easy Payment Module for Magento 2
|------|----------
|Author | `Nets eCom`
|Prefix | `EASY-M2`
|Shop Version | `2.4+`
|Version | `1.5.7`
|Guide | https://tech.nets.eu/magento
|Github | https://github.com/Nets-eCom/Magento2_easy

## CHANGELOG

### Version 1.5.7 - Released 2022-09-19
* Fixed : Canceling order in Magento.
* Fixed : Phone number issue for UK store.
* Fixed : Refund discount fixed for shipping.
* Fixed : Improved A2A Payment Methods compatibility.

### Version 1.5.6 - Released 2022-08-10
* Fixed : Order gone missing issue.
* Fixed : Option for different billing and shipping address.
* Fixed : Automatic invoice generation in case of auto capture.
* Fixed : PHP version compatibility issue.


### Version 1.5.5 - Released 2022-07-05
* Fixed : Canceling order in portal will be canceled in Magento as well.

### Version 1.5.4 - Released 2022-06-27
* Fixed : Address issue for Switzerland country in payment iframe.

### Version 1.5.3 - Released 2022-06-22
* Fixed : Order confirmation email for Swish payment.
* Fixed : Swish payment label in order detail page.
* Fixed : Order status updated from canceled to pending payment.
* Fixed : Address issue for UK country in payment iframe.
* Fixed : Payment iframe locale as per configuration.

### Version 1.5.2 - Released 2022-05-20
* Fixed : Payment method display issue fixed for Swish payment in order detail page.
* Fixed : Alert message "Payment init fail" has been replaced with valid error message.
* Fixed : Added CHF currency for Checkout.
* Fixed : Added db_schema.xml file.

### Version 1.5.1 - Released 2022-05-2
* Fixed : Payment method will be displayed for all types in admin order section.
* Fixed : Payment status will be displayed in admin order section.
* Fixed : Added webhook for charge and refund.
* Fixed : Added setting in nets configuration page to send consumer data if no selected.

### Version 1.5.0 - Released 2022-03-14
* Fixed : Currency issues, all transactions will be in base currency

### Version 1.4.6 - Released 2021-12-18
* Fixed : MerchantTermsUrl parameter in the checkout to display merchants terms and conditions
* Fixed : Internal switch between B2B and B2C when handle customer data is set to yes and company name provided in checkout page
* Fixed : Simplified "integration type" options in the settings. It is possible to select either "Embedded" or "redirect"
* Fixed : Added input fields to enter live and test authentication keys
* Fixed : Original price 0 issue in order admin section

### Version 1.4.5 - Released 2021-11-25
* Fixed : Cart clear issue

### Version 1.4.4 - Released 2021-11-18
* Fixed : Invoice calculation issue 
* Fixed : Quote id reference updation issue

### Version 1.4.3 - Released 2021-10-21
* Fixed : Invoice creation issue with Capture online
* Fixed : Hosted/Redirect payment flow issue
* Fixed : Clear mini cart on successful order
* Fixed : Partial refund with discount applied 
* Fixed : Quote id reference updation issue 

### Version 1.4.2 - Released 2021-09-30
* Fixed : Fixed calculation errors for multiple discounts & shipping taxes

### Version 1.4.1 - Released 2021-09-17
* Fixed : Added functionality for Invoice discounts

### Version 1.4.0 - Released 2021-09-14
* Fixed : Minor bug fix on calculation method

### Version 1.3.9 - Released 2021-09-06
* Fixed : Discount coupon & amount display issue in cart and order summary
* Fixed : Guest customer address re-enter on checkout window

### Version 1.3.8 - Released 2021-08-30
* Fixed : Calculation and tax rules rework
* Fixed : Discounts and shipping rework
* Update : Locale shipping, currency and language update
* Docs: added license, changelog and readme file update

### Version 1.3.6 - Released 2021-08-06
* Update - Fixes regarding Vanilla Embedded Checkout & character limit for reference and name. Fixed discount issues.

### Version 1.1.2 - Released 2021-07-27
* Update - Added system configuration for success page


### Version 1.1.1 - Released 2021-07-27
* Fixed - Amount issue for discount and tax
* Docs - Added changelog file

