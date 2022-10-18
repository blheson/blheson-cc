<?php

/**
 * Plugin Name: BB Credit Card Gateway
 * Domain Path: /Languages/
 * Plugin URI: https://blessingudor.com
 * Description: Pay with Demo Credit Card
 * Author: Blessing Udor []
 * Author URI: https://blessingudor.com
 * Version: 0.0.1
 * WC requires at least: 3.0.0
 * WC tested up to: 6.0
 * Licence: GPLv3
 * 
 * @package Blheson plugins
 */

use Automattic\Jetpack\Constants;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
function blheson_cc_gateway_class()
{
    /**
     * CC Gateway.
     *
     * Provides a CC Payment Gateway.
     *
     * @class       WC_RKFL_CC
     * @extends     WC_Payment_Gateway
     * @version     2.1.0
     * @package     WooCommerce\Classes\Payment
     */
    class WC_RKFL_CC extends WC_Payment_Gateway
    {

        /**
         * Constructor for the gateway.
         */
        public function __construct()
        {
            // Setup general properties.
            $this->setup_properties();

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Get settings.
            $this->title              = $this->get_option('title');
            $this->description        = $this->get_option('description');
            $this->instructions       = $this->get_option('instructions');
            $this->enable_for_methods = $this->get_option('enable_for_methods', array());
            $this->enable_for_virtual = $this->get_option('enable_for_virtual', 'yes') === 'yes';

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
            add_filter('woocommerce_payment_complete_order_status', array($this, 'change_payment_complete_order_status'), 10, 3);

            // Customer Emails.
            add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);
        }
        public function payment_fields()
        {
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
                .d-flex{
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
        protected function setup_properties()
        {
            $this->id                 = 'blheson_cc';
            $this->icon               = apply_filters('woocommerce_cod_icon', '');
            $this->method_title       = __('Credit Card', 'woocommerce');
            $this->method_description = __('Have your customers pay with credit card', 'woocommerce');
            $this->has_fields         = true;
        }

        /**
         * Initialise Gateway Settings Form Fields.
         */
        public function init_form_fields()
        {
            $this->form_fields = array(
                'enabled'            => array(
                    'title'       => __('Enable/Disable', 'woocommerce'),
                    'label'       => __('Enable Credit Card', 'woocommerce'),
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no',
                ),
                'title'              => array(
                    'title'       => __('Title', 'woocommerce'),
                    'type'        => 'text',
                    'description' => __('Payment method description that the customer will see on your checkout.', 'woocommerce'),
                    'default'     => __('Credit Card', 'woocommerce'),
                    'desc_tip'    => true,
                ),
                'description'        => array(
                    'title'       => __('Description', 'woocommerce'),
                    'type'        => 'textarea',
                    'description' => __('Payment method description that the customer will see on your website.', 'woocommerce'),
                    'default'     => __('Pay with credit card', 'woocommerce'),
                    'desc_tip'    => true,
                ),
                'instructions'       => array(
                    'title'       => __('Instructions', 'woocommerce'),
                    'type'        => 'textarea',
                    'description' => __('Instructions that will be added to the thank you page.', 'woocommerce'),
                    'default'     => __('Pay with Credit card.', 'woocommerce'),
                    'desc_tip'    => true,
                ),

            );
        }

        /**
         * Check If The Gateway Is Available For Use.
         *
         * @return bool
         */
        public function is_available()
        {
            $order          = null;
            $needs_shipping = false;

            // Test if shipping is needed first.
            if (WC()->cart && WC()->cart->needs_shipping()) {
                $needs_shipping = true;
            } elseif (is_page(wc_get_page_id('checkout')) && 0 < get_query_var('order-pay')) {
                $order_id = absint(get_query_var('order-pay'));
                $order    = wc_get_order($order_id);

                // Test if order needs shipping.
                if ($order && 0 < count($order->get_items())) {
                    foreach ($order->get_items() as $item) {
                        $_product = $item->get_product();
                        if ($_product && $_product->needs_shipping()) {
                            $needs_shipping = true;
                            break;
                        }
                    }
                }
            }

            $needs_shipping = apply_filters('woocommerce_cart_needs_shipping', $needs_shipping);

            // Virtual order, with virtual disabled.
            if (!$this->enable_for_virtual && !$needs_shipping) {
                return false;
            }

            // Only apply if all packages are being shipped via chosen method, or order is virtual.
            if (!empty($this->enable_for_methods) && $needs_shipping) {
                $order_shipping_items            = is_object($order) ? $order->get_shipping_methods() : false;
                $chosen_shipping_methods_session = WC()->session->get('chosen_shipping_methods');

                if ($order_shipping_items) {
                    $canonical_rate_ids = $this->get_canonical_order_shipping_item_rate_ids($order_shipping_items);
                } else {
                    $canonical_rate_ids = $this->get_canonical_package_rate_ids($chosen_shipping_methods_session);
                }

                if (!count($this->get_matching_rates($canonical_rate_ids))) {
                    return false;
                }
            }

            return parent::is_available();
        }

        /**
         * Checks to see whether or not the admin settings are being accessed by the current request.
         *
         * @return bool
         */
        private function is_accessing_settings()
        {
            if (is_admin()) {
                // phpcs:disable WordPress.Security.NonceVerification
                if (!isset($_REQUEST['page']) || 'wc-settings' !== $_REQUEST['page']) {
                    return false;
                }
                if (!isset($_REQUEST['tab']) || 'checkout' !== $_REQUEST['tab']) {
                    return false;
                }
                if (!isset($_REQUEST['section']) || 'cod' !== $_REQUEST['section']) {
                    return false;
                }
                // phpcs:enable WordPress.Security.NonceVerification

                return true;
            }

            if (Constants::is_true('REST_REQUEST')) {
                global $wp;
                if (isset($wp->query_vars['rest_route']) && false !== strpos($wp->query_vars['rest_route'], '/payment_gateways')) {
                    return true;
                }
            }

            return false;
        }


        /**
         * Converts the chosen rate IDs generated by Shipping Methods to a canonical 'method_id:instance_id' format.
         *
         * @since  3.4.0
         *
         * @param  array $order_shipping_items  Array of WC_Order_Item_Shipping objects.
         * @return array $canonical_rate_ids    Rate IDs in a canonical format.
         */
        private function get_canonical_order_shipping_item_rate_ids($order_shipping_items)
        {

            $canonical_rate_ids = array();

            foreach ($order_shipping_items as $order_shipping_item) {
                $canonical_rate_ids[] = $order_shipping_item->get_method_id() . ':' . $order_shipping_item->get_instance_id();
            }

            return $canonical_rate_ids;
        }

        /**
         * Converts the chosen rate IDs generated by Shipping Methods to a canonical 'method_id:instance_id' format.
         *
         * @since  3.4.0
         *
         * @param  array $chosen_package_rate_ids Rate IDs as generated by shipping methods. Can be anything if a shipping method doesn't honor WC conventions.
         * @return array $canonical_rate_ids  Rate IDs in a canonical format.
         */
        private function get_canonical_package_rate_ids($chosen_package_rate_ids)
        {

            $shipping_packages  = WC()->shipping()->get_packages();
            $canonical_rate_ids = array();

            if (!empty($chosen_package_rate_ids) && is_array($chosen_package_rate_ids)) {
                foreach ($chosen_package_rate_ids as $package_key => $chosen_package_rate_id) {
                    if (!empty($shipping_packages[$package_key]['rates'][$chosen_package_rate_id])) {
                        $chosen_rate          = $shipping_packages[$package_key]['rates'][$chosen_package_rate_id];
                        $canonical_rate_ids[] = $chosen_rate->get_method_id() . ':' . $chosen_rate->get_instance_id();
                    }
                }
            }

            return $canonical_rate_ids;
        }

        /**
         * Indicates whether a rate exists in an array of canonically-formatted rate IDs that activates this gateway.
         *
         * @since  3.4.0
         *
         * @param array $rate_ids Rate ids to check.
         * @return boolean
         */
        private function get_matching_rates($rate_ids)
        {
            // First, match entries in 'method_id:instance_id' format. Then, match entries in 'method_id' format by stripping off the instance ID from the candidates.
            return array_unique(array_merge(array_intersect($this->enable_for_methods, $rate_ids), array_intersect($this->enable_for_methods, array_unique(array_map('wc_get_string_before_colon', $rate_ids)))));
        }

        /**
         * Process the payment and return the result.
         *
         * @param int $order_id Order ID.
         * @return array
         */
        public function process_payment($order_id)
        {
            $order = wc_get_order($order_id);

            if ($order->get_total() > 0) {
                // Mark as processing or on-hold (payment won't be taken until delivery).
                $order->update_status(apply_filters('woocommerce_cod_process_payment_order_status', $order->has_downloadable_item() ? 'on-hold' : 'processing', $order), __('Payment to be made upon delivery.', 'woocommerce'));
            } else {
                $order->payment_complete();
            }

            // Remove cart.
            WC()->cart->empty_cart();

            // Return thankyou redirect.
            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url($order),
            );
        }

        /**
         * Output for the order received page.
         */
        public function thankyou_page()
        {
            if ($this->instructions) {
                echo wp_kses_post(wpautop(wptexturize($this->instructions)));
            }
        }

        /**
         * Change payment complete order status to completed for CC orders.
         *
         * @since  3.1.0
         * @param  string         $status Current order status.
         * @param  int            $order_id Order ID.
         * @param  WC_Order|false $order Order object.
         * @return string
         */
        public function change_payment_complete_order_status($status, $order_id = 0, $order = false)
        {
            if ($order && $this->id  === $order->get_payment_method()) {
                $status = 'completed';
            }
            return $status;
        }

        /**
         * Add content to the WC emails.
         *
         * @param WC_Order $order Order object.
         * @param bool     $sent_to_admin  Sent to admin.
         * @param bool     $plain_text Email format: plain text or HTML.
         */
        public function email_instructions($order, $sent_to_admin, $plain_text = false)
        {
            if ($this->instructions && !$sent_to_admin && $this->id === $order->get_payment_method()) {
                echo wp_kses_post(wpautop(wptexturize($this->instructions)) . PHP_EOL);
            }
        }
    }
}


function blheson_cc_add_gateway_class()
{
    if (class_exists('WC_Payment_Gateway_CC')) {

        $methods[] = 'WC_RKFL_CC';
    }

    return $methods;
}


add_action('plugins_loaded', 'blheson_cc_gateway_class');

add_filter('woocommerce_payment_gateways',  'blheson_cc_add_gateway_class');
