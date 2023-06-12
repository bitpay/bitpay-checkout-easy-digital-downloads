<?php

namespace BitPayEddLib;

use WP_REST_Request;

class BitPayEddAbandonOrder {
	private BitPayCheckoutTransactions $bitpay_checkout_transactions;

	public function __construct( BitPayCheckoutTransactions $bitpay_checkout_transactions ) {
		$this->bitpay_checkout_transactions = $bitpay_checkout_transactions;
	}

	public function execute( WP_REST_Request $request ): void {
		$data     = $request->get_params();
		$order_id = $data['orderid'];

		$this->bitpay_checkout_transactions->update_last_pending_status( 'invoice_expired', $order_id );
		edd_update_order_status( $order_id, 'abandoned' );

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	}
}
