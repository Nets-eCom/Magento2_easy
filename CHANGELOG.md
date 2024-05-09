# NETS A/S - Magento 2 Payment Module
============================================

| Module       | Nets Easy Payment Module for Magento 2     |
|--------------|--------------------------------------------|
| Author       | `Nets eCom`                                |
| Prefix       | `EASY-M2`                                  |
| Shop Version | `2.4+`                                     |
| Version      | `1.6.10`                                    |
| Guide        | https://tech.nets.eu/magento               |
| Github       | https://github.com/Nets-eCom/Magento2_easy |

## CHANGELOG

### Version 1.6.10 - Released - 2024-03-13
- Fix: remove double checkout payment initialization.

### Version 1.6.9 - Released - 2024-03-04
* Fix: Plugin incompatible with PHP 7.4.

### Version 1.6.8 - Released - 2024-02-14
* Fix: Order ID was not updated after reservation in some cases.

### Version 1.6.7 - Released - 2024-02-01
* Update: Create order on click Pay button (embedded flow)
* Fix: PHP 8.2 deprecation.
* Fix: Wrong country codes found in Magento 2 plugin

### Version 1.6.6 - Released - 2023-11-08
* Update: Improved naming of plugin configuration fields in the admin panel.
* Update: Repository added to Packagist.
* Update: Documentation Guide links in README.md.
* Update: Order is now terminated on cancel.
* Fixed: Plugin incompatible with Adobe Marketplace.

### Version 1.6.5 - Released - 2023-10-19
* Update: allow to configure how order items are sent to the API
* Fix: invoice order on payment.charge.created.v2 event (autocharge)


### Version 1.6.4 - Released - 2023-10-12
* Update: Items in shopping basket.
* Fix: Mismatch in orders/amounts when updating cart after created payment.
* Update: On embedded flow with an order creation before the payment.
* Update: PHP 8.2 deprecation.

### Version 1.6.3 - Released - 2023-09-11
* Update : Create order after payment is created (redirect flow)  

### Version 1.6.2 - Released 2023-03-28
* Fixed : Amount mismatch issue for bundle product.
* Fixed : Amount mismatch issue for Afterpay charge and refund.
* Fixed : Twice address fill issue for virtual product in redirect payment flow.

### Version 1.6.1 - Released 2023-03-01
* Fixed : Quote Id and order not creating issue for Hosted Payment Flow.
* Fixed : Magento Terms and Conditions missing in payment.

### Version 1.6.0 - Released 2023-02-07
* Fixed : Quote Id and order not creating issue for Embedded Payment Flow.
* Fixed : Guest Customer Email Id missing.

### Version 1.5.9 - Released 2022-12-08
* Fixed : Quote Id and order not creating issue.
* Fixed : Improved A2A Payment Methods compatibility.
* Update : Added more logs for critical points.
* Update : Configure Payment Name and Logo and display on checkout page.
* Update : Nets plugin latest version notification on configure page.

### Version 1.5.8 - Released 2022-11-01
* Fixed : Order flow change.
* Fixed : Discount code calculation updated.
* Fixed : Partial charge and partial refund issue.

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

