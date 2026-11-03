<?php

namespace BitPayEddLib;

use BitPaySDK\Exceptions\BitPayException;
use BitPaySDK\Exceptions\InvoiceQueryException;
use BitPaySDK\Model\Facade;
use BitPaySDK\Model\Invoice\Invoice;
use stdClass;
use WP_REST_Request;

class BitPayIpnProcess {
	private BitPayCheckoutTransactions $bitpay_checkout_transactions;
	private BitPayClientFactory $bitpay_client_factory;

	public function __construct(
		BitPayCheckoutTransactions $bitpay_checkout_transactions,
		BitPayClientFactory $bitpay_client_factory
	) {
		$this->bitpay_checkout_transactions = $bitpay_checkout_transactions;
		$this->bitpay_client_factory        = $bitpay_client_factory;
	}

	/**
	 * Process ipn request from bitpay to update payment status.
	 *
	 * @param WP_REST_Request $request Request data.
	 *
	 * @throws BitPayException Exception throws during create bitpay pos client.
	 * @throws InvoiceQueryException Exception throws during get bitpay invoice from api.
	 */
	public function execute( WP_REST_Request $request ): void {
		$data = $request->get_body();

		$data  = json_decode( $data );
		$event = $data->event;

		$data = $data->data;

		$invoice_id = $data->id;

		// SECURITY (IM-77): resolve the order from our own stored invoice ->
		// order mapping, never from the callback body. Otherwise a genuinely
		// paid invoice can be replayed against any other order id.
		$order_id = $this->bitpay_checkout_transactions->get_order_id_by_invoice_id( $invoice_id );
		if ( null === $order_id ) {
			return;
		}

		$bitpay_client  = $this->bitpay_client_factory->create();
		$bitpay_invoice = $bitpay_client->getInvoice( $invoice_id, Facade::POS, false );

		// SECURITY (IM-77): for events that advance an order toward paid, the
		// invoice must belong to this order and match its expected amount and
		// currency. Defence in depth in case the invoice lookup itself is
		// tampered with (DNS, compromised infrastructure).
		$paid_events = array( 'invoice_completed', 'invoice_confirmed', 'invoice_paidInFull' );
		if ( in_array( $event->name, $paid_events, true )
			&& ! $this->invoice_matches_order( $bitpay_invoice, $order_id ) ) {
			return;
		}

		switch ( $event->name ) {
			case 'invoice_completed':
			case 'invoice_confirmed':
				$this->process_completed( $bitpay_invoice->getStatus(), $invoice_id, $order_id, $event->name );
				break;
			case 'invoice_paidInFull':
				$this->process_processing( $bitpay_invoice->getStatus(), $invoice_id, $order_id, $event->name );
				break;
			case 'invoice_declined':
			case 'invoice_failedToConfirm':
				$this->process_failed( $bitpay_invoice->getStatus(), $invoice_id, $order_id, $event->name );
				break;
			case 'invoice_expired':
				$this->process_abandoned( $bitpay_invoice->getStatus(), $invoice_id, $order_id, $event->name );
				break;
			case 'invoice_refundComplete':
				$this->process_refunded( $bitpay_invoice->getStatus(), $invoice_id, $order_id, $event->name );
				break;
		}
	}

	private function get_edd_options(): array {
		global $edd_options;

		return $edd_options;
	}

	private function invoice_matches_order( Invoice $bitpay_invoice, int $order_id ): bool {
		if ( (string) $bitpay_invoice->getOrderId() !== (string) $order_id ) {
			return false;
		}

		$expected_total    = (float) edd_get_payment_amount( $order_id );
		$expected_currency = edd_get_payment_currency_code( $order_id );

		if ( 0.00001 < abs( (float) $bitpay_invoice->getPrice() - $expected_total ) ) {
			return false;
		}

		$invoice_currency  = strtoupper( (string) $bitpay_invoice->getCurrency() );
		$expected_currency = strtoupper( (string) $expected_currency );
		if ( $invoice_currency !== $expected_currency ) {
			return false;
		}

		return true;
	}

	private function process_completed(
		string $invoice_status,
		string $invoice_id,
		int $order_id,
		string $event_name
	): void {
		if ( ! in_array( $invoice_status, array( 'confirmed', 'complete' ), true ) ) {
			return;
		}

		$note = 'BitPay Invoice ID: <a target = "_blank" href = "' . BitPayEndpoint::get_url(
			$this->get_edd_options()['test_mode'],
			$invoice_id
		) . '">' . $invoice_id . '</a> processing has been completed.';

		edd_insert_payment_note( $order_id, $note );
		$this->bitpay_checkout_transactions->update_status( $event_name, $order_id, $invoice_id );
		edd_update_order_status( $order_id, 'completed' );
	}

	private function process_processing(
		string $invoice_status,
		string $invoice_id,
		int $order_id,
		string $event_name
	): void {
		if ( 'paid' !== $invoice_status ) {
			return;
		}

		$note = 'BitPay Invoice ID: <a target = "_blank" href = "' . BitPayEndpoint::get_url(
			$this->get_edd_options()['test_mode'],
			$invoice_id
		) . '">' . $invoice_id . '</a> is processing.';

		edd_insert_payment_note( $order_id, $note );
		$this->bitpay_checkout_transactions->update_status( $event_name, $order_id, $invoice_id );
		edd_update_order_status( $order_id, 'processing' );
	}

	private function process_failed(
		string $invoice_status,
		string $invoice_id,
		int $order_id,
		string $event_name
	): void {
		if ( ! in_array( $invoice_status, array( 'invalid', 'declined' ), true ) ) {
			return;
		}

		$note = 'BitPay Invoice ID: <a target = "_blank" href = "' . BitPayEndpoint::get_url(
			$this->get_edd_options()['test_mode'],
			$invoice_id
		) . '">' . $invoice_id . '</a> has become invalid because of network congestion.  Order will automatically update when the status changes.';

		edd_insert_payment_note( $order_id, $note );
		$this->bitpay_checkout_transactions->update_status( $event_name, $order_id, $invoice_id );
		edd_update_order_status( $order_id, 'failed' );
	}

	private function process_abandoned(
		string $invoice_status,
		string $invoice_id,
		int $order_id,
		string $event_name
	): void {
		if ( 'expired' !== $invoice_status ) {
			return;
		}

		$this->bitpay_checkout_transactions->update_status( $event_name, $order_id, $invoice_id );
		edd_update_order_status( $order_id, 'abandoned' );
	}

	private function process_refunded(
		string $invoice_status,
		string $invoice_id,
		int $order_id,
		string $event_name
	): void {
		// Only an invoice that was paid can be refunded.
		if ( ! in_array( $invoice_status, array( 'paid', 'confirmed', 'complete' ), true ) ) {
			return;
		}

		$note = 'BitPay Invoice ID: <a target = "_blank" href = "' . BitPayEndpoint::get_url(
			$this->get_edd_options()['test_mode'],
			$invoice_id
		) . '">' . $invoice_id . '</a> has been refunded.';

		edd_insert_payment_note( $order_id, $note );
		$this->bitpay_checkout_transactions->update_status( $event_name, $order_id, $invoice_id );
		edd_update_order_status( $order_id, 'refunded' );
	}
}
