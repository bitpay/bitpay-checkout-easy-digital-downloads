<?php

use BitPaySDK\Exceptions\BitPayException;
use BitPaySDK\Exceptions\InvoiceQueryException;

class BitPayPluginSetup
{
    private BitPayCheckoutTransactions $bitPayCheckoutTransactions;
    private BitPayEddAddSettings $bitPayEddAddSettings;
    private BitPayEddProcessPayment $bitPayEddProcessPayment;
    private BitPayClientFactory $bitPayClientFactory;
    private BitPayIpnProcess $bitPayIpnProcess;
    private BitPayEddPrintEnqueueScripts $bitPayEddPrintEnqueueScripts;
    private BitPayEddAbandonOrder $bitPayEddAbandonOrder;

    public function __construct()
    {
        $this->bitPayClientFactory = new BitPayClientFactory();
        $this->bitPayCheckoutTransactions = new BitPayCheckoutTransactions();
        $this->bitPayEddAddSettings = new BitPayEddAddSettings();
        $this->bitPayEddProcessPayment = new BitPayEddProcessPayment(
            $this->bitPayCheckoutTransactions,
            $this->bitPayClientFactory
        );
        $this->bitPayIpnProcess = new BitPayIpnProcess(
            $this->bitPayCheckoutTransactions,
            $this->bitPayClientFactory
        );
        $this->bitPayEddPrintEnqueueScripts = new BitPayEddPrintEnqueueScripts();
        $this->bitPayEddAbandonOrder = new BitPayEddAbandonOrder($this->bitPayCheckoutTransactions);
    }

    public function execute(): void
    {
        register_activation_hook(__FILE__, array($this, 'setupPlugin'));

        add_filter('edd_payment_gateways', array($this, 'registerGateway'));
        add_filter('edd_accepted_payment_icons', array($this, 'addIcon'));
        add_filter('edd_settings_sections_gateways', array($this, 'registerGatewaySection'));
        add_filter('edd_settings_gateways', array($this, 'addEddSettingToPaymentGatewaySection'));

        add_action('edd_gateway_bp_checkout_edd', array($this, 'processPayment'));
        add_action('edd_bp_checkout_edd_cc_form', array($this, 'removeDefaultCcForm'));
        add_action('rest_api_init', function () {
            register_rest_route('bitpay-edd/ipn', '/status', array(
                'methods' => 'POST,GET',
                'callback' => array($this, 'processIpn'),
            ));
            register_rest_route('bitpay-edd/cartfix', '/update', array(
                'methods' => 'POST,GET',
                'callback' => array($this, 'abandonOrder')
            ));
        });
        add_action('wp_enqueue_scripts', array($this, 'printEnqueueScripts'));
    }

    public function setupPlugin(): void
    {
        if (!function_exists('curl_version')) {
            $errorMessage = 'cUrl needs to be installed/enabled for BitPay Checkout for Easy Digital Downloads to function';
            $plugins_url = admin_url('plugins.php');
            wp_die($errorMessage . '<br><a href="' . $plugins_url . '">Return to plugins screen</a>');
        }

        $this->bitPayCheckoutTransactions->createTable();
    }

    public function registerGateway(array $gateways): array
    {
        $gateways['bp_checkout_edd'] = array(
            'admin_label' => 'BitPay Checkout',
            'checkout_label' => __('BitPay Checkout', 'pw_edd'),
            'icons' => array(
                plugins_url('/../bitpaycheckout.png', __FILE__),
            ),
        );

        return $gateways;
    }

    public function addIcon(array $icons): array
    {
        $icons[plugins_url('/../bitpaycheckout.png', __FILE__)] = 'BitPay Checkout';
        return $icons;
    }

    // register the action to remove default CC form
    public function removeDefaultCcForm(): void
    {
        return;
    }


    public function registerGatewaySection(array $gatewaySections): array
    {
        $gatewaySections['bp_checkout_edd'] = __('BitPay Checkout', 'easy-digital-downloads');
        return $gatewaySections;
    }

    public function addEddSettingToPaymentGatewaySection(array $gatewaySections): array
    {
        return $this->bitPayEddAddSettings->execute($gatewaySections);
    }

    /**
     * @throws BitPayException
     */
    public function processPayment(array $purchaseData): void
    {
        $this->bitPayEddProcessPayment->execute($purchaseData);
    }

    //abandon the order because they closed the modal
    public function abandonOrder(WP_REST_Request $request): void
    {
        $this->bitPayEddAbandonOrder->execute($request);
    }

    /**
     * @throws InvoiceQueryException
     * @throws BitPayException
     */
    public function processIpn(WP_REST_Request $request): void
    {
        $this->bitPayIpnProcess->execute($request);
    }

    public function printEnqueueScripts(): void
    {
        $this->bitPayEddPrintEnqueueScripts->execute();
    }
}
