<?php
/**
 * Copyright (c) 2019-present Qualiteam software Ltd. All rights reserved.
 */

use XPaymentsCloud\Model\Payment as XpPayment;

class WC_Gateway_XPaymentsCloud extends WC_Payment_Gateway
{
    private $logger;

    function __construct()
    {
        $this->id = 'xpayments_cloud';
        $this->method_title = 'X-Payments Cloud';
        $this->method_description = 'Accept Credit or Debit cards via X-Payments Cloud, a PCI-DSS Level 1 Certified service.';
        $this->has_fields = true;

        $this->logger = wc_get_logger();

        $this->init_form_fields();
        $this->init_settings();

        add_action( 'xpayments_continue_payment', array( $this, 'continue_payment') );

        // admin only
        if ( is_admin() ) {

            // save settings
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        }

        $this->title = 'Credit or Debit card by X-Payments';
//        $this->title = $this->get_option( 'title' );

    }

    /**
     * Output the admin options table.
     */
    public function admin_options() {
        global $hide_save_button;
        $hide_save_button = true;

        wp_enqueue_script( 'xpayments_connect_js', plugins_url( 'assets/js/connect.js', __FILE__ ), array(), '0.1.0' );
        wp_enqueue_style('xpayments_connect_css', plugins_url( 'assets/css/connect.css', __FILE__ ));

        $account = $this->get_option( 'account' );
        $quickaccessKey = $this->get_option( 'quickaccess_key' );

        echo '<h2>' . esc_html( $this->get_method_title() );
        wc_back_link( __( 'Return to payments', 'woocommerce' ), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) );
        echo '</h2>';
//        echo '<table class="form-table">' . $this->generate_settings_html( $this->get_form_fields(), false ) . '</table>';
        echo <<<HTML
<table class="form-table">
<div id="xpayments-iframe-container" style="">
</div>
</table>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    var widget = new XPaymentsConnect();

    widget.init({
      container: '#xpayments-iframe-container',
      topElement: '#mainform',
      quickAccessKey: '$quickaccessKey',
      account: '$account',
    }).on('alert', function(params) {
      alert(params.message);
    }).on('config', function(params) {
      var data = {};
      data['woocommerce_xpayments_cloud_account'] = params.account;
      data['woocommerce_xpayments_cloud_api_key'] = params.apiKey;
      data['woocommerce_xpayments_cloud_secret_key'] = params.secretKey;
      data['woocommerce_xpayments_cloud_widget_key'] = params.widgetKey;
      data['woocommerce_xpayments_cloud_quickaccess_key'] = params.quickAccessKey;
      data['save'] = '1';
      data['_wpnonce'] = jQuery('#_wpnonce').val();
      
      jQuery.post(document.location.href, data);  
    });
    widget.load();
  });

</script>

HTML;

    }

    function init_form_fields() {
        $this->form_fields = array(
/*
            'enabled' => array(
                'title' => __( 'Enable/Disable', 'woocommerce' ),
                'type' => 'checkbox',
                'label' => __( 'Enable X-Payments Cloud', 'woocommerce' ),
                'default' => 'yes'
            ),
            'title' => array(
                'title' => __( 'Title', 'woocommerce' ),
                'type' => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
                'default' => __( 'Credit or Debit card by X-Payments', 'woocommerce' ),
                'desc_tip'      => true,
            ),
*/
            'account' => array(
                'title' => __( 'X-Payments Account', 'woocommerce' ),
                'type' => 'text',
                'default' => ''
            ),
            'api_key' => array(
                'title' => __( 'X-Payments Api Key', 'woocommerce' ),
                'type' => 'text',
                'default' => ''
            ),
            'secret_key' => array(
                'title' => __( 'X-Payments Secret Key', 'woocommerce' ),
                'type' => 'text',
                'default' => ''
            ),
            'widget_key' => array(
                'title' => __( 'X-Payments Widget Key', 'woocommerce' ),
                'type' => 'text',
                'default' => ''
            ),
        );
    }

    public function payment_fields()
    {
        wp_enqueue_script( 'xpayments_widget_js', plugins_url( 'assets/js/widget.js', __FILE__ ), array(), '0.1.0' );

        $account = $this->get_option( 'account' );
        $widgetKey = $this->get_option( 'widget_key' );
        $total = $this->get_order_total();
        $currency = get_woocommerce_currency();
        $paymentId = $this->id;


        $customerId = WC()->customer->get_meta('xpayments_customer_id') ?: '';
        $showSaveCard = WC()->customer->get_id() ? 'true' : 'false';

        // Display payment form
        require $this->get_plugin_path() . '/includes/payment-form.php';

    }

    public function process_payment($order_id) {

        $api = $this->initClient();

        $token = stripslashes($_POST['xpayments_token']);

        $order = wc_get_order( $order_id );

        $returnUrl = wc_get_endpoint_url( 'xpayments-continue-payment', $order_id, wc_get_page_permalink( 'checkout' ) );
        $returnUrl = str_replace( 'http:', 'https:', $returnUrl );

        $callbackUrl = str_replace( 'http:', 'https:', add_query_arg( 'wc-api', get_class($this), home_url( '/' ) ) );

        try {
            $response = $api->doPay(
                $token,
                $order->get_order_number(),
                get_user_meta($order->get_customer_id(),'xpayments_customer_id', true) ?: '',
                $this->prepareCart($order),
                $returnUrl,
                $callbackUrl
            );

            $payment = $response->getPayment();
            $status = $payment->status;
            $note = $payment->message;

            if (!is_null($response->redirectUrl)) {
                // Should redirect to continue payment
                $this->setOrderXpid($order, $payment->xpid);
                $result = array(
                    'result'   => 'success',
                    'redirect' => $response->redirectUrl,
                );

            } else {

                $result = $this->processPaymentFinish($order, $payment);

                if (!$result) {
                    $this->setTopError($note);
                }

            }

        } catch (\XPaymentsCloud\ApiException $exception) {
            $result = array();
            $note = $exception->getMessage();
            $this->log('Error: ' . $note);
            $message = $exception->getPublicMessage();
            if (!$message) {
                $message = 'Failed to process the payment!';
            }
            $this->setTopError($message);

        }

        return $result;
    }

    /**
     * Process return from 3-D Secure form and complete payment
     */
    public function continue_payment()
    {
        $order_id = intval($_GET['xpayments-continue-payment']);
        $order = wc_get_order( $order_id );

        $xpid = $this->getOrderXpid($order);

        $redirectUrl = wc_get_endpoint_url( '', '', wc_get_page_permalink( 'checkout' ) );

        if ($xpid) {

            $api = $this->initClient();

            try {
                $response = $api->doContinue(
                    $xpid
                );

                $payment = $response->getPayment();

                $result = $this->processPaymentFinish($order, $payment);
                if ($result) {
                    $redirectUrl = $result['redirect'];
                } else {
                    $message = $payment->message;
                }

            } catch (\XPaymentsCloud\ApiException $exception) {

                $note = $exception->getMessage();
                // Add note to log, but exact error shouldn't be shown to customer
                $this->log('Error: ' . $note);
                $message = 'Failed to process the payment!';
            }
/*
            $this->transaction->setNote($note);
            $this->transaction->setStatus($result);
*/
        } else {
            // Invalid non-XP transaction
            $message = 'Transaction was lost';
        }

        if (!empty($message)) {
            $this->setTopError($message);
        }
        wp_redirect( $redirectUrl );
        exit;
    }

    /**
     * Process callbacks from X-P
     *
     */
    public function process_callback()
    {
        //TODO: implement
    }

    private function setTopError($message) {
        wc_add_notice( __('Payment error:', 'woothemes') . ' ' . $message, 'error' );
    }

    /**
     * Prepare shopping cart data
     *
     * @return array
     */
    public function prepareCart(WC_Order $order)
    {
        $description = 'Order #' . $order->get_order_number();

        $merchantEmail = sanitize_email( get_option( 'woocommerce_email_from_address' ) );

        $billing_address  = $order->get_formatted_billing_address();
        $shipping_address = $order->get_formatted_shipping_address();

        $user = $order->get_user();

        $result = array(
            'login'                => $user->user_login,
            'items'                => array(),
            'currency'             => get_woocommerce_currency(),
            'shippingCost'         => 0.00,
            'taxCost'              => 0.00,
            'discount'             => 0.00,
            'totalCost'            => 0.00,
            'description'          => $description,
            'merchantEmail'        => $merchantEmail,

        );

        if (
            $billing_address
            && $shipping_address
        ) {

            $result['billingAddress'] = $this->prepareAddress($order);
            $result['shippingAddress'] = $this->prepareAddress($order, 'shipping');

        } elseif (
            $billing_address
            && !$shipping_address
        ) {

            $result['billingAddress'] = $result['shippingAddress'] = $this->prepareAddress($order);

        } else {

            $result['billingAddress'] = $result['shippingAddress'] = $this->prepareAddress($order, 'shipping');
        }

        $order_items = $order->get_items();

        foreach ( $order_items as $order_item ) {
            /** @var WC_Product $product */
            $product = $order_item->get_product();

            $itemElement = array(
                'sku'      => $product ? $product->get_sku() : '',
                'name'     => $order_item->get_name(),
                'price'    => $this->roundCurrency( $order->get_item_subtotal( $order_item, false ) ),
                'quantity' => $order_item->get_quantity(),
            );

            if (!$itemElement['sku']) {
                $itemElement['sku'] = 'N/A';
            }

            if (!$itemElement['name']) {
                $itemElement['name'] = 'N/A';
            }

            $result['items'][] = $itemElement;
        }

        $result['shippingCost'] = $this->roundCurrency(
            $order->get_shipping_total()
        );
        $result['taxCost'] = $this->roundCurrency(
            $order->get_total_tax()
        );
        $result['discount'] = $this->roundCurrency(
            abs($order->get_discount_total())
        );

        $result['totalCost'] = $this->roundCurrency($order->get_total());

        return $result;
    }

    protected function setOrderXpid(WC_Order $order, $xpid) {
        $order->add_meta_data('xpayments_xpid', $xpid, true);
        $order->save_meta_data();
    }

    protected function getOrderXpid(WC_Order $order) {
        return $order->get_meta('xpayments_xpid');
    }

    /**
     * Returns path to plugin without a trailing slash
     * @return string
     */
    public function get_plugin_path() {
        static $path = null;

        if (is_null($path)) {
            $path = plugin_dir_path( __FILE__ );
        }

        return $path;
    }

    /**
     * Round currency
     *
     * @param float $data Data
     *
     * @return float
     */
    protected function roundCurrency($data)
    {
        return sprintf('%01.2f', round($data, 2));
    }

    /**
     * Prepare address data
     *
     *
     * @return array
     */
    protected function prepareAddress(WC_Order $order, $type = 'billing')
    {
        $result = array();

        $addressFields = array(
            'firstname' => 'N/A',
            'lastname'  => '',
            'address'   => 'N/A',
            'city'      => 'N/A',
            'state'     => 'N/A',
            'country'   => 'XX', // WA fix for MySQL 5.7 with strict mode
            'zipcode'   => 'N/A',
            'phone'     => '',
            'fax'       => '',
            'company'   => '',
            'email'     => '',
        );

        $wcFieldMatch = array(
            'firstname' => 'first_name',
            'lastname' => 'last_name',
            'zipcode'  => 'postcode',
        );

        foreach ($addressFields as $field => $defValue) {

            $func_prefix = 'get_' . $type . '_';

            if ('address' == $field) {
                $result[$field] =
                    $order->{$func_prefix . 'address_1'}()
                    . ' '
                    . $order->{$func_prefix . 'address_2'}();
            } else {
                $wcField = (isset($wcFieldMatch[$field])) ? $wcFieldMatch[$field] : $field;
                $func = $func_prefix . $wcField;

                if (method_exists($order, $func)) {
                    $result[$field] = $order->$func();
                }
            }

            if (empty($result[$field])) {
                $result[$field] = $defValue;
            }
        }

        return $result;
    }

    /**
     * Finalize initial transaction
     *
     * @param WC_Order $order
     * @param \XPaymentsCloud\Model\Payment $payment
     *
     * @return array
     */
    private function processPaymentFinish(WC_Order $order, XpPayment $payment)
    {
        $result = array();

        $this->setTransactionDataCells($order, $payment);

        if ($payment->customerId) {
            update_user_meta($order->get_user_id(), 'xpayments_customer_id', $payment->customerId);
        }

        $status = $payment->status;

        if (
            XpPayment::AUTH == $status
            || XpPayment::CHARGED == $status
        ) {
            $order->payment_complete();
            WC()->cart->empty_cart();

            $result = array(
                'result'   => 'success',
                'redirect' => $this->get_return_url( $order ),
            );

        }

        return $result;
    }


    /**
     * Sets all required transaction data cells for further operations
     *
     * @param WC_Order $order
     * @param \XPaymentsCloud\Model\Payment $payment
     */
    private function setTransactionDataCells(WC_Order $order, XpPayment $payment)
    {
        $this->setOrderXpid($order, $payment->xpid);

        /*
        $transaction->setDataCell('xpaymentsMessage', $payment->message, 'Message');

        $actions = [
            'capture' => 'Capture',
            'void' => 'Void',
            'refund' => 'Refund',
        ];

        foreach ($actions as $action => $cellName) {
            $can = ($payment->isTransactionSupported($action)) ? static::ACTION_ALLOWED : static::ACTION_NOTALLOWED;
            if (static::ACTION_ALLOWED == $can) {
                if ($payment->isTransactionSupported($action . 'Multi')) {
                    $can = static::ACTION_MULTI;
                } elseif ($payment->isTransactionSupported($action . 'Part')) {
                    $can = static::ACTION_PART;
                }
            }
            $transaction->setDataCell('xpayments' . $cellName, $can, $cellName);

        }
        */
    }

    /**
     * Logging method.
     *
     * @param string $message Log message.
     * @param string $level Optional. Default 'info'. Possible values:
     *                      emergency|alert|critical|error|warning|notice|info|debug.
     */
    public function log( $message, $level = 'info' ) {
        self::$logger->log( $level, $message, array( 'source' => $this->id ) );
    }

    /*
     * Load X-Payments Cloud SDK
     */
    private function loadApi()
    {
        require_once( plugin_dir_path( __FILE__ ) . 'lib/XPaymentsCloud/Client.php' );
    }

    /*
     * Init SDK Client
     *
     * @return \XPaymentsCloud\Client
     */
    protected function initClient()
    {
        $this->loadApi();

        return new \XPaymentsCloud\Client(
            $this->get_option('account'),
            $this->get_option('api_key'),
            $this->get_option('secret_key')
        );
    }

}
