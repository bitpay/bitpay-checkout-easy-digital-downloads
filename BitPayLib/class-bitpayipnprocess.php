<?php

namespace BitPayLib;

use BitPaySDK\Exceptions\BitPayException;
use BitPaySDK\Exceptions\InvoiceQueryException;
use BitPaySDK\Model\Facade;
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

		// phpcs:ignore
		$order_id   = $data->orderId;
		$invoice_id = $data->id;

		$bitpay_client  = $this->bitpay_client_factory->create();
		$bitpay_invoice = $bitpay_client->getInvoice( $invoice_id, Facade::POS, false );

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
				$this->process_refunded( $bitpay_invoice->getStatus(), $order_id, $event->name );
				break;
		}
	}

	private function get_edd_options(): array {
		global $edd_options;

		return $edd_options;
	}

	private function process_completed(
		string $invoice_status,
		string $invoice_id,
		int $order_id,
		string $event_name
	): void {
		if ( ! in_array( $invoice_status, array( 'confirmed', 'completed' ), true ) ) {
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
		string $invoice_id,
		int $order_id,
		string $event_name
	): void {
		$note = 'BitPay Invoice ID: <a target = "_blank" href = "' . BitPayEndpoint::get_url(
			$this->get_edd_options()['test_mode'],
			$invoice_id
		) . '">' . $invoice_id . '</a> has been refunded.';

		edd_insert_payment_note( $order_id, $note );
		$this->bitpay_checkout_transactions->update_status( $event_name, $order_id, $invoice_id );
		edd_update_order_status( $order_id, 'refunded' );
	}
}
