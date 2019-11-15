<?php
/**
 * Plugin Name: WooCommerce X-Payments Cloud connector (Beta)
 * Description: Process and store credit cards right on your website, accept recurring payments and reorders. X-Payments will take the PSD2/SCA & PCI DSS burden off your shoulders.
 * Version: 0.2.1
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
