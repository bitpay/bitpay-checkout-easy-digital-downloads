<?php

namespace BitPayLib;

use BitPaySDK\Model\Invoice\Buyer;
use BitPaySDK\Model\Invoice\Invoice;

class BitPayInvoiceFactory {

	public function create(
		float $price,
		string $order_id,
	): Invoice {
		$invoice = new Invoice(
			$price,
			'USD'
		);

		$invoice->setExtendedNotifications( true );
		$invoice->setRedirectURL( $this->get_redirect_url( $order_id ) );
		$invoice->setNotificationURL( $this->get_notification_url() );
		$invoice->setOrderId( $order_id );
		$invoice->setBuyer( $this->get_buyer() );

		return $invoice;
	}

	private function get_redirect_url( string $order_id ): string {
		return edd_get_success_page_uri() . '?order_id=' . $order_id;
	}

	private function get_notification_url(): string {
		return get_home_url() . '/wp-json/bitpay-edd/ipn/status';
	}

	private function get_buyer(): Buyer {
		if ( $this->get_edd_option( 'bitpay_checkout_capture_email' ) !== 1 ) {
			return new Buyer();
		}

		$current_user = wp_get_current_user();

		if ( ! $current_user->user_email ) {
			return new Buyer();
		}

		$buyer_info = new Buyer();
		$buyer_info->setName( $current_user->display_name );
		$buyer_info->setEmail( $current_user->user_email );

		return $buyer_info;
	}

	private function get_edd_option( string $option_name ): string {
		global $edd_options;

		return $edd_options[ $option_name ];
	}
}
