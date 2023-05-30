<?php

namespace BitPayLib;

class BitPayEddPrintEnqueueScripts {

	public function execute(): void {
		/* phpcs:ignore */
		wp_enqueue_script( 'remote-bitpayquickpay-js', 'https://bitpay.com/bitpay.min.js', null, null, true );
		wp_enqueue_script( 'bitpayquickpay-js', plugins_url( '../js/bitpay_edd.js', __FILE__ ), null, wp_rand( 111, 999 ), false );
		$mode = BitPayEndpoint::get_type( $this->get_option( 'test_mode' ) );
		?>
		<script type="text/javascript">
			if (window.location.href.indexOf("&bpedd=1&invoiceID=") > -1) {
				setTimeout(function () {
						jQuery('#primary').css('opacity', '0.3');
					},
					200
				);

				var urlParams = new URLSearchParams(window.location.search);
				var $oid = urlParams.get('order_id')
				var $iid = urlParams.get('invoiceID')
				$cart_url = "<?php echo esc_js( edd_get_checkout_uri() ); ?>"
				$fix_url = "<?php echo esc_js( get_home_url() . '/wp-json/bitpay-edd/cartfix/update' ); ?>"

				setTimeout(function () {
						showBPInvoice('<?php echo esc_js( $mode ); ?>', $iid, $oid, $cart_url, $fix_url)
					},
					250
				);
			}
		</script>
		<?php
	}

	private function get_option( string $option_name ): string {
		global $edd_options;

		return $edd_options[ $option_name ];
	}
}
