<?php
/**
 * Plugin Name: BitPay Checkout for Easy Digital Downloads
 * Plugin URI: http://www.bitpay.com
 * Description: Create Invoices and process through BitPay.  Configure in your <a href ="edit.php?post_type=download&page=edd-settings&tab=gateways">Easy Digital Downloads->Payment Gateways</a>.
 * Version: 2.0.0
 * Author: BitPay
 * Author URI: mailto:integrations@bitpay.com?subject=BitPay Checkout for Easy Digital Downloads
 */

if (!defined('ABSPATH')): exit;endif;

require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
#autoloader
function BP_EDD_autoloader($class)
{
    if (strpos($class, 'BitPay') !== false):
        if (!class_exists('BitPayLib/' . $class, false)):
            #doesn't exist so include it
            include 'BitPayLib/' . $class . '.php';
        endif;
    endif;
}

spl_autoload_register('BP_EDD_autoloader');

$bitPayPluginSetup = new BitPayPluginSetup();
$bitPayPluginSetup->execute();
