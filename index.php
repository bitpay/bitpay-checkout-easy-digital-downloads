<?php
/**
 * Plugin to add BitPay payments to EDD.
 *
 * @package     BitPayEddLib
 * Plugin Name: BitPay Checkout for Easy Digital Downloads
 * Plugin URI: http://www.bitpay.com
 * Description: Create Invoices and process through BitPay.  Configure in your <a href ="edit.php?post_type=download&page=edd-settings&tab=gateways">Easy Digital Downloads->Payment Gateways</a>.
 * Version: 2.0.0
 * Author: BitPay
 * Author URI: mailto:integrations@bitpay.com?subject=BitPay Checkout for Easy Digital Downloads
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

$files_to_load = array(
	'class-bitpaycheckouttransactions.php',
	'class-bitpayclientfactory.php',
	'class-bitpayeddabandonorder.php',
	'class-bitpayeddaddsettings.php',
	'class-bitpayeddprintenqueuescripts.php',
	'class-bitpayeddprocesspayment.php',
	'class-bitpayendpoint.php',
	'class-bitpayinvoicefactory.php',
	'class-bitpayipnprocess.php',
	'class-bitpaypluginsetup.php',
);

foreach ( $files_to_load as $file ) {
	include_once 'BitPayEddLib/' . $file;
}

use BitPayEddLib\BitPayPluginSetup;

$bit_pay_plugin_setup = new BitPayPluginSetup();
$bit_pay_plugin_setup->execute();
