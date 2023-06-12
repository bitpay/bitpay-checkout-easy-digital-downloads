<?php

namespace BitPayEddLib;

use BitPaySDK\Client;
use BitPaySDK\Env;
use BitPaySDK\Exceptions\BitPayException;
use BitPaySDK\PosClient;

class BitPayClientFactory {

	/**
	 * Create PosClient
	 *
	 * @throws BitPayException BitPayException class.
	 */
	public function create(): Client {
		return new PosClient( $this->get_bitpay_token(), $this->get_environment() );
	}

	private function get_bitpay_token(): string {
		/* dev or prod token */
		switch ( $this->get_option( 'test_mode' ) ) {
			case 'true':
			case '1':
			default:
				return $this->get_option( 'bitpay_checkout_token_dev' );
			case 'false':
			case '0':
				return $this->get_option( 'bitpay_checkout_token_prod' );
		}
	}

	private function get_environment(): string {
		switch ( $this->get_option( 'test_mode' ) ) {
			case 'true':
			case '1':
			default:
				return Env::TEST;
			case 'false':
			case '0':
				return Env::PROD;
		}
	}

	private function get_option( string $option_name ): string {
		global $edd_options;

		return $edd_options[ $option_name ];
	}
}
