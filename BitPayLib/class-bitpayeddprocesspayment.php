<?php

namespace BitPayLib;

use BitPaySDK\Model\Facade;
use BitPaySDK\Exceptions\BitPayException;
use BitPaySDK\Model\Invoice\Invoice;

class BitPayEddProcessPayment {

	private BitPayCheckoutTransactions $bitpay_checkout_transactions;
	private BitPayClientFactory $bitpay_client_factory;
	private BitPayInvoiceFactory $bitpay_invoice_factory;

	public function __construct(
		BitPayCheckoutTransactions $bitpay_checkout_transactions,
		BitPayClientFactory $bitpay_client_factory
	) {
		$this->bitpay_checkout_transactions = $bitpay_checkout_transactions;
		$this->bitpay_client_factory        = $bitpay_client_factory;
		$this->bitpay_invoice_factory       = new BitPayInvoiceFactory();
	}

	/**
	 * Create EDD payment and BitPay invoice.
	 *
	 * @param array $purchase_data Data form checkout.
	 *
	 * @throws BitPayException BitPayException class.
	 */
	public function execute( array $purchase_data ): void {
		/* payment processing happens here */
		$errors = edd_get_errors();
		if ( $errors ) {
			/* if errors are present, send the user back to the purchase page, so they can be corrected */
			edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );

			return;
		}

		$payment = $this->get_payment( $purchase_data );
		/* record the pending payment */
		$order_id = edd_insert_payment( $payment );

		if ( ! $order_id ) {
			edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );

			return;
		}

		$bitpay_invoice = $this->create_bitpay_invoice( floatval( $purchase_data['price'] ), $order_id );

		$invoice_id = $bitpay_invoice->getId();
		$this->update_cookies( $invoice_id );

		$this->add_order_note( $bitpay_invoice->getOrderId(), $invoice_id );
		edd_empty_cart();
		/* use the modal if '1', otherwise redirect */
		if ( intval( $this->get_edd_options()['bitpay_checkout_flow'] ) === 2 ) {
			// phpcs:ignore
			wp_redirect( $bitpay_invoice->getUrl() );

			return;
		}

		edd_redirect( $bitpay_invoice->getRedirectURL() . '&bpedd=1&$invoiceID=' . $invoice_id );
	}

	private function get_payment( array $purchase_data ): array {
		return array(
			'price'        => $purchase_data['price'],
			'date'         => $purchase_data['date'],
			'user_email'   => $purchase_data['user_email'],
			'purchase_key' => $purchase_data['purchase_key'],
			'currency'     => $this->get_edd_options()['currency'],
			'downloads'    => $purchase_data['downloads'],
			'cart_details' => $purchase_data['cart_details'],
			'user_info'    => $purchase_data['user_info'],
			'status'       => 'pending',
		);
	}

	/**
	 * Create bitpay invoice by api.
	 *
	 * @param float $price invoice price.
	 * @param int   $order_id edd order id.
	 *
	 * @return Invoice
	 * @throws BitPayException BitPayException class.
	 */
	private function create_bitpay_invoice( float $price, int $order_id ): Invoice {
		$bitpay_client  = $this->bitpay_client_factory->create();
		$bitpay_invoice = $this->bitpay_invoice_factory->create( $price, trim( $order_id ) );

		return $bitpay_client->createInvoice( $bitpay_invoice, Facade::POS, false );
	}

	private function update_cookies( string $invoice_id ): void {
		/* set a cookie for redirects and updating the order status */
		setcookie( 'bitpay-edd-invoice-id', $invoice_id, time() + ( 86400 * 30 ), '/' );
	}

	private function get_edd_options(): array {
		global $edd_options;

		return $edd_options;
	}

	private function add_order_note( int $order_id, string $transaction_id ): void {
		$note = 'BitPay Invoice ID: <a target = "_blank" href = "' . BitPayEndpoint::get_url(
			$this->get_edd_options()['test_mode'],
			$transaction_id
		) . '">' . $transaction_id . '</a> is pending.';

		edd_insert_payment_note( $order_id, $note );
		edd_update_order_status( $order_id, 'pending' );
		$this->bitpay_checkout_transactions->create_transaction( $order_id, $transaction_id, 'pending' );
	}
}
