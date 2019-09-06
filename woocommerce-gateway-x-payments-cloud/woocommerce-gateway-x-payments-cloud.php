<?php
/**
 * Plugin Name: WooCommerce X-Payments Cloud (Beta)
 * Description: This module integrates X-Payments Cloud into your store
 * Version: 0.1.0
 * Author: X-Cart Payments
 * Author URI: https://x-payments.com
 */

defined( 'ABSPATH' ) or die();

function init_xpayments_plugin() {
    require_once( plugin_dir_path( __FILE__ ) . 'class-wc-x-payments-cloud.php' );
}

function add_xpayments_gateway_class( $methods ) {
    $methods[] = 'WC_Gateway_XPaymentsCloud';
    return $methods;
}

function check_for_xpayments_return() {
    if ( isset($_GET['xpayments-continue-payment']) ) {
        // Start the gateways
        WC()->payment_gateways();
        do_action( 'xpayments_continue_payment' );
    }
}

add_action( 'plugins_loaded', 'init_xpayments_plugin' );
add_action( 'init', 'check_for_xpayments_return' );

add_filter( 'woocommerce_payment_gateways', 'add_xpayments_gateway_class' );
add_action( 'woocommerce_api_wc_gateway_xpaymentscloud', array( 'WC_Gateway_XPaymentsCloud', 'process_callback' ) );
