<?php

namespace BitPayEddLib;

class BitPayEndpoint {
	public static function get_type( int $mode ): string {
		if ( $mode ) {
			return 'test';
		} else {
			return 'prod';
		}
	}

	public static function get_url( int $mode, string $invoice_id ): string {
		if ( $mode ) {
			return '//test.bitpay.com/dashboard/payments/' . $invoice_id;
		} else {
			return '//bitpay.com/dashboard/payments/' . $invoice_id;
		}
	}
}
