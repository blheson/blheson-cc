<?php

/**
 * Plugin Name: BB Credit Card Gateway
 * Domain Path: /Languages/
 * Plugin URI: https://blessingudor.com
 * Description: Pay with Demo Credit Card
 * Author: Blessing Udor
 * Author URI: https://blessingudor.com
 * Version: 0.2
 * WC requires at least: 3.0.0
 * WC tested up to: 6.0
 * Licence: GPLv3
 *
 * @package Blheson plugins
 */

use Automattic\Jetpack\Constants;

define( 'WC_BLHESON_CC_MAIN_FILE', __FILE__ );

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
function blheson_cc_gateway_class() {
	/**
	 * CC Gateway.
	 *
	 * Provides a CC Payment Gateway.
	 *
	 * @class       WC_BLHESON_CC
	 * @extends     WC_Payment_Gateway
	 * @version     2.1.0
	 * @package     WooCommerce\Classes\Payment
	 */
	class WC_BLHESON_CC extends WC_Payment_Gateway {

		/**
		 * Constructor for the gateway.
		 */
		public function __construct() {
			$this->id = 'blheson_cc';

			$this->has_fields = true;
			 // Setup general properties.
			$this->setup_properties();

			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();

			// Get settings.
			$this->title              = $this->get_option( 'title' );
			$this->description        = $this->get_option( 'description' );
			$this->instructions       = $this->get_option( 'instructions' );
			$this->enable_for_methods = $this->get_option( 'enable_for_methods', array() );
			$this->enable_for_virtual = $this->get_option( 'enable_for_virtual', 'yes' ) === 'yes';

			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		}
		public function payment_fields() {
			?>
			<style>
				.form_group {
					margin: 0 20px;
				}

				.form_group input {
					width: 100%;
					height: 50px;
				}

				.form_group input.short {
					width: 100%;
					height: 50px;
				}

				.form_group label {
					font-size: 10px;
				}

				.d-flex {
					display: flex;
					justify-content: space-between;
				}
			</style>
			<div class="form_group">
				<label for="">CARD NUMBER</label>
				<input type="text" placeholder="0000 0000 0000 0000">
			</div>
			<div class="form_group">
				<label for="">CARD HOLDER</label>
				<input type="text" placeholder="xxxxx xxxxx">
			</div>
			<div class="d-flex">
				<div class="form_group">
					<label for="">EXPIRY DATE</label>
					<input type="text" placeholder="04/22">
				</div>
				<div class="form_group ">
					<label for="">CVV</label>
					<input type="number" class="short" placeholder="000">
				</div>
			</div>



			<?php
		}

		/**
		 * Setup general properties for the gateway.
		 */
		protected function setup_properties() {
			 $this->id                = 'blheson_cc';
			$this->icon               = apply_filters( 'woocommerce_cod_icon', '' );
			$this->method_title       = __( 'Credit Card', 'woocommerce' );
			$this->method_description = __( 'Have your customers pay with credit card', 'woocommerce' );
			$this->has_fields         = true;
		}

		/**
		 * Initialise Gateway Settings Form Fields.
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled'      => array(
					'title'       => __( 'Enable/Disable', 'woocommerce' ),
					'label'       => __( 'Enable Credit Card', 'woocommerce' ),
					'type'        => 'checkbox',
					'description' => '',
					'default'     => 'no',
				),
				'title'        => array(
					'title'       => __( 'Title', 'woocommerce' ),
					'type'        => 'text',
					'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce' ),
					'default'     => __( 'Credit Card', 'woocommerce' ),
					'desc_tip'    => true,
				),
				'description'  => array(
					'title'       => __( 'Description', 'woocommerce' ),
					'type'        => 'textarea',
					'description' => __( 'Payment method description that the customer will see on your website.', 'woocommerce' ),
					'default'     => __( 'Pay with credit card', 'woocommerce' ),
					'desc_tip'    => true,
				),
				'instructions' => array(
					'title'       => __( 'Instructions', 'woocommerce' ),
					'type'        => 'textarea',
					'description' => __( 'Instructions that will be added to the thank you page.', 'woocommerce' ),
					'default'     => __( 'Pay with Credit card.', 'woocommerce' ),
					'desc_tip'    => true,
				),

			);
		}

		// /**
		// * Checks to see whether or not the admin settings are being accessed by the current request.
		// *
		// * @return bool
		// */
		// private function is_accessing_settings() {
		// if ( is_admin() ) {
		// 		// phpcs:disable WordPress.Security.NonceVerification
		// if ( ! isset( $_REQUEST['page'] ) || 'wc-settings' !== $_REQUEST['page'] ) {
		// return false;
		// }
		// if ( ! isset( $_REQUEST['tab'] ) || 'checkout' !== $_REQUEST['tab'] ) {
		// return false;
		// }
		// if ( ! isset( $_REQUEST['section'] ) || 'cod' !== $_REQUEST['section'] ) {
		// return false;
		// }
		// 		// phpcs:enable WordPress.Security.NonceVerification

		// return true;
		// }

		// if ( Constants::is_true( 'REST_REQUEST' ) ) {
		// global $wp;
		// if ( isset( $wp->query_vars['rest_route'] ) && false !== strpos( $wp->query_vars['rest_route'], '/payment_gateways' ) ) {
		// return true;
		// }
		// }

		// return false;
		// }

		/**
		 * Process the payment and return the result.
		 *
		 * @param int $order_id Order ID.
		 * @return array
		 */
		public function process_payment( $order_id ) {
			$order = wc_get_order( $order_id );

			if ( $order->get_total() > 0 ) {
				// Mark as processing or on-hold (payment won't be taken until delivery).
				$order->update_status( apply_filters( 'woocommerce_cod_process_payment_order_status', $order->has_downloadable_item() ? 'on-hold' : 'processing', $order ), __( 'Payment to be made upon delivery.', 'woocommerce' ) );
			} else {
				$order->payment_complete();
			}

			// Remove cart.
			WC()->cart->empty_cart();

			// Return thankyou redirect.
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);
		}

	}
}


function blheson_cc_add_gateway_class( $methods ) {
	if ( class_exists( 'WC_Payment_Gateway_CC' ) ) {

		$methods[] = 'WC_BLHESON_CC';
	}

	return $methods;
}


add_action( 'plugins_loaded', 'blheson_cc_gateway_class' );

add_filter( 'woocommerce_payment_gateways', 'blheson_cc_add_gateway_class', 99 );

/**
 * Registers WooCommerce Blocks integration.
 */
function blheson_cc_woocommerce_block_support() {
	require_once 'includes/class-wc-gateway-blheson-cc.php';
	if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {

		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			static function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
				$payment_method_registry->register( new WC_Gateway_Custom_Blheson_CC_Blocks_Support() );
			}
		);
	}
}
add_action( 'woocommerce_blocks_loaded', 'blheson_cc_woocommerce_block_support' );
