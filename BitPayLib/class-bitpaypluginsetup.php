<?php

namespace BitPayLib;

use BitPaySDK\Exceptions\BitPayException;
use BitPaySDK\Exceptions\InvoiceQueryException;
use WP_REST_Request;

class BitPayPluginSetup {

	private BitPayCheckoutTransactions $bitpay_checkout_transactions;
	private BitPayEddAddSettings $bitpay_edd_add_settings;
	private BitPayEddProcessPayment $bitpay_edd_process_payment;
	private BitPayClientFactory $bitpay_client_factory;
	private BitPayIpnProcess $bitpay_ipn_process;
	private BitPayEddPrintEnqueueScripts $bitpay_edd_print_enqueue_scripts;
	private BitPayEddAbandonOrder $bitpay_edd_abandon_order;

	public function __construct() {
		$this->bitpay_client_factory            = new BitPayClientFactory();
		$this->bitpay_checkout_transactions     = new BitPayCheckoutTransactions();
		$this->bitpay_edd_add_settings          = new BitPayEddAddSettings();
		$this->bitpay_edd_process_payment       = new BitPayEddProcessPayment(
			$this->bitpay_checkout_transactions,
			$this->bitpay_client_factory
		);
		$this->bitpay_ipn_process               = new BitPayIpnProcess(
			$this->bitpay_checkout_transactions,
			$this->bitpay_client_factory
		);
		$this->bitpay_edd_print_enqueue_scripts = new BitPayEddPrintEnqueueScripts();
		$this->bitpay_edd_abandon_order         = new BitPayEddAbandonOrder( $this->bitpay_checkout_transactions );
	}

	public function execute(): void {
		register_activation_hook( __FILE__, array( $this, 'setup_plugin' ) );

		add_filter( 'edd_payment_gateways', array( $this, 'register_gateway' ) );
		add_filter( 'edd_accepted_payment_icons', array( $this, 'add_icon' ) );
		add_filter( 'edd_settings_sections_gateways', array( $this, 'register_gateway_section' ) );
		add_filter( 'edd_settings_gateways', array( $this, 'add_edd_setting_to_payment_gateway_section' ) );

		add_action( 'edd_gateway_bp_checkout_edd', array( $this, 'process_payment' ) );
		add_action( 'edd_bp_checkout_edd_cc_form', array( $this, 'remove_default_cc_form' ) );
		add_action(
			'rest_api_init',
			function () {
				register_rest_route(
					'bitpay-edd/ipn',
					'/status',
					array(
						'methods'  => 'POST,GET',
						'callback' => array( $this, 'process_ipn' ),
					)
				);
				register_rest_route(
					'bitpay-edd/cartfix',
					'/update',
					array(
						'methods'  => 'POST,GET',
						'callback' => array( $this, 'abandon_order' ),
					)
				);
			}
		);
		add_action( 'wp_enqueue_scripts', array( $this, 'print_enqueue_scripts' ) );
	}

	public function setup_plugin(): void {
		if ( ! function_exists( 'curl_version' ) ) {
			$error_message = 'cUrl needs to be installed/enabled for BitPay Checkout for Easy Digital Downloads to function';
			$plugins_url   = admin_url( 'plugins.php' );
			wp_die( esc_html( $error_message ) . '<br><a href="' . esc_html( $plugins_url ) . '">Return to plugins screen</a>' );
		}

		$this->bitpay_checkout_transactions->create_table();
	}

	public function register_gateway( array $gateways ): array {
		$gateways['bp_checkout_edd'] = array(
			'admin_label'    => 'BitPay Checkout',
			'checkout_label' => __( 'BitPay Checkout', 'pw_edd' ),
			'icons'          => array(
				plugins_url( '/../bitpaycheckout.png', __FILE__ ),
			),
		);

		return $gateways;
	}

	public function add_icon( array $icons ): array {
		$icons[ plugins_url( '/../bitpaycheckout.png', __FILE__ ) ] = 'BitPay Checkout';

		return $icons;
	}

	/**
	 * Remove cc form from bitpay checkout.
	 */
	public function remove_default_cc_form(): void {
	}

	public function register_gateway_section( array $gateway_sections ): array {
		$gateway_sections['bp_checkout_edd'] = __( 'BitPay Checkout', 'easy-digital-downloads' );

		return $gateway_sections;
	}

	public function add_edd_setting_to_payment_gateway_section( array $gateway_sections ): array {
		return $this->bitpay_edd_add_settings->execute( $gateway_sections );
	}

	/**
	 * Create edd order, payment and bitpay invoice for checkout
	 *
	 * @param array $purchase_data Checkout data.
	 *
	 * @throws BitPayException Exception throws during create bitpay pos client.
	 */
	public function process_payment( array $purchase_data ): void {
		$this->bitpay_edd_process_payment->execute( $purchase_data );
	}

	/**
	 * Abandon the order because user closed the modal
	 *
	 * @param WP_REST_Request $request Checkout data.
	 */
	public function abandon_order( WP_REST_Request $request ): void {
		$this->bitpay_edd_abandon_order->execute( $request );
	}

	/**
	 * Process ipn request from bitpay to update payment status.
	 *
	 * @param WP_REST_Request $request Request data.
	 *
	 * @throws BitPayException Exception throws during create bitpay pos client.
	 * @throws InvoiceQueryException Exception throws during get bitpay invoice from api.
	 */
	public function process_ipn( WP_REST_Request $request ): void {
		$this->bitpay_ipn_process->execute( $request );
	}

	public function print_enqueue_scripts(): void {
		$this->bitpay_edd_print_enqueue_scripts->execute();
	}
}
