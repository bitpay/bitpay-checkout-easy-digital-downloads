=== BitPay Checkout for Easy Digital Downloads ===
Contributors: bitpay
Tags: bitcoin, bitcoin cash, payments, bitpay, cryptocurrency, payment gateway
Requires at least: 3.1
Tested up to: 6.2.2
Requires PHP: 8.0
Stable tag: 2.0.0
License: MIT License (MIT)
License URI: https://github.com/bitpay/bitpay-checkout-easy-digital-downloads/blob/master/LICENSE

The most secure and fastest way to accept crypto payments (Bitcoin, Bitcoin Cash, etc).

== Description ==

== BitPay Payment Gateway plugin for Easy Digital Downloads ==

= Key features =

* Accept bitcoin and bitcoin cash payments from payment protocol compatible wallets
* Price in your local currency
* Get settled via Bank transfer (EUR, USD, GBP or any of the supported [fiat currencies](https://bitpay.com/docs/settlement)), BTC, BCH or stable coins (GUSD, USDC)
* By design, chargebacks are not possible with cryptocurrency payments
* Have an overview of all your bitcoin and bitcoin cash payments in your BitPay merchant account at https://bitpay.com/dashboard
* Refund your customers in bitcoin or bitcoin cash in your BitPay merchant dashboard at https://bitpay.com/dashboard/payments

= Customer journey =

1. The customer is adding items to his shopping card and proceeds to checkout - the total amount is $100 USD.
2. The customer selects BitPay as checkout method.
3. A BitPay invoice is generated, the customer selects one of the supported cryptocurrency to complete the payment. The invoice will display an amount to pay in the selected cryptocurrency, at an exchange rate locked for 15 minutes.
4. The customer completes the payment using a compatible wallet within the 15 min window.
5. Once the transaction is fully confirmed on the blockchain, BitPay notifies the merchant and the corresponding amount is credited to the BitPay merchant account minus our 1% processing fee - thus $99 USD in this example.

== Installation ==

= Requirements =

* This plugin requires [Easy Digital Downloads](https://wordpress.org/plugins/easy-digital-downloads/).
* A BitPay merchant account ([Test](http://test.bitpay.com) and [Production](http://www.bitpay.com))

= Plugin installation =

1. Get started by signing up for a [BitPay merchant account](https://bitpay.com/dashboard/signup)
2. Look for the BitPay plugin via the [Wordpress Plugin Manager](https://codex.wordpress.org/Plugins_Add_New_Screen). From your Wordpress admin panel, go to Plugins > Add New > Search plugins and type **BitPay**
3. Select **BitPay for Easy Digital Downloads** and click on **Install Now** and then on **Activate Plugin**

After the plugin is activated, BitPay will appear in the Easy Digital Downloads > Payment Gateways section.

= Plugin configuration =

After you have installed the BitPay plugin, the configuration steps are:

1. Create an API token from your BitPay merchant dashboard:
	* Login to your BitPay merchant account and go to the [API token settings](https://bitpay.com/dashboard/merchant/api-tokens)
	* click on the **Add new token** button: indicate a token label (for instance: Easy Digital Downloads), uncheck "Require Authentication" and click on the **Add Token** button
	* Copy the token value
2. Log in to your WordPress admin panel, select Easy Digital Downloads > Payment Gateway and enable BitPay Checkout
3. Click the BitPay Checkout heading to configure
	* Paste the token value into the appropriate field: **Development Token** for token copied from the sandbox environment (test.bitpay.com) and **Production Token** for token copied from the live environment (bitpay.com)
	* Click "Save changes" at the bottom of the page
	* To use BitPay Test environment, select Test Mode from the Payment Gateways section 

= Order fulfillment =
	
This plugin also includes an IPN (Instant Payment Notification) endpoint that will update your Easy Digital Download order status. An order note will automatically be added with a link to the BitPay invoice *(will open in a new window)*:

* When the customer initiates a transaction from his wallet to pay the BitPay invoice, the status of the Easy Digital Downloads order will change to **Processing**
* When the transaction is confirmed by BitPay, the status of the Easy Digital Downloads order will change to **Completed**. The order will be safe to ship, allow access to downloadable products, etc.
* If a BitPay invoice expires before the customer completed the payment, the Easy Digital Downloads order will change to **Cancelled**.
* If you refund a BitPay invoice from your BitPay merchant dashboard, the Easy Digital Downloads order will change to **Refunded** once the refund is processed by BitPay.

== Frequently Asked Questions ==

= How do I pay a BitPay invoice? =
You can pay a BitPay invoice with one of the compatible wallets. You can either scan the QR code, click on the "open in wallet" button or copy/paste the payment URL via a compatible wallet.

More information about paying a BitPay invoice can be found [here.](https://support.bitpay.com/hc/en-us/articles/115005559826-How-do-I-pay-a-BitPay-merchant-without-a-bitcoin-address-)

= Does BitPay have a test environment? =
Yes, you can create an account on BitPay's sandbox environment to process payments on testnet. You will also need to setup a wallet on testnet to make test transactions. More information about the test environment can be found [here.](https://bitpay.com/docs/testing)

= The BitPay plugin does not work =
If BitPay invoices are not created, please check the following:

* The minimum invoice amount is $1 USD. Please make sure you are trying to create a BitPay invoice for $1 USD or more (or your currency equivalent).
* Check your current approved processing limits in your [BitPay merchant account](https://bitpay.com/dashboard/verification)

= I need support from BitPay =
When contacting BitPay support, please describe your issue and attach screenshots 

You can contact our support team via the following form https://bitpay.com/request-help/wizard

== Screenshots ==

1. BitPay merchant dashboard - create a new POS token
2. BitPay merchant dashboard - Point of Sale token created
3. Wordpress Easy Digital Downloads - BitPay plugin settings (1)
4. Wordpress Easy Digital Downloads - BitPay plugin settings (2)
5. BitPay checkout option - example
6. BitPay hosted invoice - modal option. Displayed to the user after he clicked the "Pay with BitPay" button
7. BitPay hosted invoice - cryptocurrency selected
8. BitPay hosted invoice - Customer clicked on the "open in wallet", this opens the compatible wallet installed on the device which automatically retrieves the payment information.
9. The customer confirmed the payment via his compatible wallet. The BitPay invoice is then marked as paid.
10. BitPay merchant dashboard - the invoice previously paid is recorded under the "Payments" section.
11. BitPay merchant dashboard - detailed invoice view
12. Wordpress Easy Digital Downloads - order view

== Changelog ==

= 2.0.0 =
* Rewrote plugin to use PHP 8.0 and EDD 3.1
* Updated plugin to use BitPay SDK 8.0.1
* The cart is cleaned after place order

= 1.1.1911 =
* Fixed issue where IPN updates may not process correctly

= 1.1.1910 =
* Fixed issue where updates might not appear correctly

= 1.0.0.4 =
* Changed speed setting so users can defined in BitPay dashboard

= 1.0.0.3 =
* IPN update

= 1.0.0.2 =
* Bug squashing

= 1.0.0.1 =
* Updated to check for server requirements.  To verify, deactivate then reactivate the plugin (your settings will be saved)
