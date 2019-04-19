## Build Status

[![Build Status](https://travis-ci.org/bitpay/bitpay-checkout-easy-digital-downloads.svg?branch=master)](https://travis-ci.org/bitpay/bitpay-checkout-easy-digital-downloads)

# Notice

This is a Community-supported project.

If you are interested in becoming a maintainer of this project, please contact us at integrations@bitpay.com. Developers at BitPay will attempt to work along the new maintainers to ensure the project remains viable for the foreseeable future.

# Description

Embed a shortcode on any page or post to instantly accept Bitcoin payments.

# Quick Setup

This version requires the following

* A BitPay merchant account ([Test](http://test.bitpay.com) or [Production](http://www.bitpay.com))
* An API Token ([Test](https://test.bitpay.com/dashboard/merchant/api-tokens) or [Production](https://bitpay.com/dashboard/merchant/api-tokens)
	* When setting up your token, **uncheck** the *Require Authentication button*


# Plugin Fields

After the plugin is activated, BitPay Checkout will appear as a gateway in the *Easy Digital Downloads > Payment Gateway* section

* **Merchant Tokens**
	* A ***development*** or ***production*** token will need to be set
* **Auto Capture Email**
	* If set to ***yes***, BitPay will automatically pass along the users email as part of the order.  If ***no***, they will be prompted to enter one (for refund purposes from BitPay)

* **Checkout Flow**
	* If set to ***Redirect***, then the user will be sent to an invoice at BitPay.com to complete their payment, and then redirected to the merchant site.  	
	* If set to ***Modal***, the user willl stay on the merchant site and complete their payment.
	
# How to use

* Enable the plugin
* In the *Easy Digital Downloads Settings->Payment Gateways* section, set the Test Mode to true (if testing)
* Enable the Payment Gateway, and optionally add the Payment Method Icon and click *Save Changes*
* Add your token, etc in the BitPay Checkout settings, and click *Save Changes*
* BitPay Checkout will now appear as a payment option when users checkout

# IPN
BitPay Checkout provides an integrated IPN service that will update orders as the status changes.

Initial orders will be set to a **Pending** state, then progress to **Processing**, and finally to **Completed**.  If an invoices is **Expired** (ie, someone creates an order but never finishes the checkout), the IPN will remove that order.

