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

function check_for_xpayments_return() {
    if ( isset($_GET['xpayments-continue-payment']) ) {
        // Start the gateways
        WC()->payment_gateways();
        do_action( 'xpayments_continue_payment' );
    } else if ( isset($_GET['xpayments-callback']) ) {
        // Start the gateways
        WC()->payment_gateways();
        do_action('xpayments_process_callback');
    }

}

add_action( 'plugins_loaded', 'init_xpayments_plugin' );
add_action( 'init', 'check_for_xpayments_return' );

add_filter( 'woocommerce_payment_gateways', 'add_xpayments_gateway_class' );
