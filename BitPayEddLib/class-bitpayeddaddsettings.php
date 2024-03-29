<?php

namespace BitPayEddLib;

class BitPayEddAddSettings {

	public function execute( array $gateway_settings ): array {
		$gateway_settings['bp_checkout_edd'] = $this->get_settings();

		return $gateway_settings;
	}

	private function get_settings(): array {
		$setting = array(
			array(
				'id'   => 'bp_checkout_edd_settings',
				'name' => '<strong>' . __( 'BitPay Checkout for Easy Digital Downloads', 'pw_edd' ) . '</strong>',
				'desc' => __( 'Configure your BitPay integration', 'pw_edd' ),
				'type' => 'header',
			),
			array(
				'id'   => 'bp_checkout_edd_desc',
				'name' => '',
				'type' => 'descriptive_text',
				'desc' => __(
					'If you have not created a BitPay Merchant Token, you can create one on your BitPay Dashboard.<br><a href = "https://test.bitpay.com/dashboard/merchant/api-tokens" target = "_blank">(Test)</a>  or <a href= "https://www.bitpay.com/dashboard/merchant/api-tokens" target = "_blank">(Production)</a> </p><br>To switch between the development and production environment, enable/disable <strong>Test Mode</strong> on the <a href = "edit.php?post_type=download&page=edd-settings&tab=gateways&section=main">Payment Gateways</a> page.',
					'pw_edd'
				),
			),
			array(
				'id'   => 'bitpay_checkout_token_dev',
				'name' => __( 'Development Token', 'pw_edd' ),
				'desc' => __(
					'<br>Your <b>development</b> merchant token.  <a href = "https://test.bitpay.com/dashboard/merchant/api-tokens" target = "_blank">Create one here</a>.',
					'pw_edd'
				),
				'type' => 'text',
				'size' => 'regular',
			),
			array(
				'id'   => 'bitpay_checkout_token_prod',
				'name' => __( 'Production Token', 'pw_edd' ),
				'desc' => __(
					'<br>Your <b>production</b> merchant token.  <a href = "https://www.bitpay.com/dashboard/merchant/api-tokens" target = "_blank">Create one here</a>.',
					'pw_edd'
				),
				'type' => 'text',
				'size' => 'regular',
			),
			array(
				'id'      => 'bitpay_checkout_capture_email',
				'name'    => __( 'Auto-Capture Email', 'pw_edd' ),
				'desc'    => __(
					'<br>Should BitPay try to auto-add the client\'s email address?  If <b>Yes</b>, the client will not be able to change the email address on the BitPay invoice.  If <b>No</b>, they will be able to add their own email address when paying the invoice.',
					'pw_edd'
				),
				'type'    => 'select',
				'size'    => 'regular',
				'options' => $this->get_checkout_capture_email_options(),

			),
			array(
				'id'      => 'bitpay_checkout_flow',
				'name'    => __( 'Checkout Flow', 'pw_edd' ),
				'desc'    => sprintf(
					/* translators: %s is replaced by "get_bloginfo" function */
					__(
						'<br>If this is set to <b>Redirect</b>, then the customer will be redirected to <b>BitPay</b> to checkout, and return to the checkout page once the payment is made.<br>If this is set to <b>Modal</b>, the user will stay on <b>%s</b> and complete the transaction.',
						'pw_edd'
					),
					get_bloginfo( 'name', null )
				),
				'type'    => 'select',
				'size'    => 'regular',
				'options' => $this->get_checkout_flow_options(),

			),
		);

		return apply_filters( 'edd_bp_checkout_settings', $setting );
	}

	private function get_checkout_flow_options(): array {
		$options = array(
			'1' => __( 'Modal', 'easy-digital-downloads' ),
			'2' => __( 'Redirect', 'easy-digital-downloads' ),
		);

		return apply_filters( 'bpcedd_cflow', $options );
	}

	private function get_checkout_capture_email_options(): array {
		$options = array(
			'0' => __( 'No', 'easy-digital-downloads' ),
			'1' => __( 'Yes', 'easy-digital-downloads' ),
		);

		return apply_filters( 'bpcedd_get_yes_no', $options );
	}
}
