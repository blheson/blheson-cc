<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

class WC_Gateway_Custom_Blheson_CC_Blocks_Support extends AbstractPaymentMethodType {
	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'blheson_cc';

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$blheson_cc_gateway_settings = get_option( 'woocommerce_blheson_cc_settings', array() );
		$this->settings              = $blheson_cc_gateway_settings;
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		$payment_gateways_class = WC()->payment_gateways();
		$payment_gateways       = $payment_gateways_class->payment_gateways();
		return $payment_gateways['blheson_cc']->is_available();
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		$payment_gateways_class = WC()->payment_gateways();
		$payment_gateways       = $payment_gateways_class->payment_gateways();
		$gateway                = $payment_gateways['blheson_cc'];

		return array(
			'title'       => $this->get_setting( 'title' ),
			'description' => $this->get_setting( 'description' ),
			'supports'    => array_filter( $gateway->supports, array( $gateway, 'supports' ) ),

		);
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$script_asset_path = plugins_url( '/assets/js/blocks/frontend/blocks.asset.php', WC_BLHESON_CC_MAIN_FILE );
		$script_asset      = file_exists( $script_asset_path )
		? require $script_asset_path
		: array(
			'dependencies' => array(),
			'version'      => '0.1.0',
		);

		$script_url = plugins_url( '/assets/js/blocks/frontend/blocks.js', WC_BLHESON_CC_MAIN_FILE );

		wp_register_script(
			'wc-blheson-cc-blocks',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		return array( 'wc-blheson-cc-blocks' );
	}
}
