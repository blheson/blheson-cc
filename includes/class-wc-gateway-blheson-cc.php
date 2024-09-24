<?php

final class WC_Gateway_Custom_Blheson_CC_Blocks_Support extends \Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType {


	protected $name = 'blheson_cc';

	public function initialize() {
		$blheson_cc_gateway_settings = get_option( 'woocommerce_blheson_cc_settings', array() );
		$this->settings              = $blheson_cc_gateway_settings;
	}

	public function is_active() {
		return true;
	}

	public function get_payment_method_data() {
		return array(
			'title'       => 'Custom Payment CC',
			'description' => 'Youtube Custom CC',
		);
	}

	public function get_payment_method_script_handles() {
		$script_asset_path = plugins_url( '/assets/js/blocks.assets.php', ROOT_FILE_PLUGIN_CC );
		$script_asset      = file_exists( $script_asset_path ) ? require $script_asset_path : array(
			'dependencies' => array(),
			'version'      => '0.1.0',
		);
		$script_asset_url  = plugins_url( '/assets/js/blocks/blocks.js', ROOT_FILE_PLUGIN_CC );
		wp_register_script( 'wc-blheson-cc-blocks', $script_asset_url, $script_asset['dependencies'], $script_asset['version'], true );
		return array( 'wc-blheson-cc-blocks' );
	}
}

