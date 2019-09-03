<?php
/**
 * Plugin Name: WooCommerce X-Payments Cloud
 * Description: This module integrates your store with X-Payments Cloud
 * Version: 1.0.0
 * Author: X-Cart Payments
 * Author URI: https://x-payments.com
 */

defined( 'ABSPATH' ) or die();

function init_xpayments_plugin() {
    require_once( plugin_dir_path( __FILE__ ) . 'class-wc-x-payments-cloud.php' );
}

function add_xpayments_gateway_class( $methods ) {
    $methods[] = 'WC_XPaymentsCloud';
    return $methods;
}

add_action('plugins_loaded', 'init_xpayments_plugin');
add_filter('woocommerce_payment_gateways', 'add_xpayments_gateway_class');
