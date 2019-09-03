<?php
/**
 * Copyright (c) 2019-present Qualiteam software Ltd. All rights reserved.
 */

use XPaymentsCloud\Model\Payment as XpPayment;

class WC_XPaymentsCloud extends WC_Payment_Gateway
{

    function __construct()
    {
        $this->id = 'xpayments_cloud';
        $this->method_title = 'X-Payments Cloud';
        $this->method_description = 'Here will be description';
        $this->has_fields = true;

        $this->init_form_fields();
        $this->init_settings();

        // admin only
        if ( is_admin() ) {

            // save settings
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        }

        $this->title = $this->get_option( 'title' );

    }

    function init_form_fields() {
        $this->form_fields = array(
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
        wp_enqueue_script( 'xpayments_widget_js', plugins_url( 'assets/js/widget.js', __FILE__ ), array(), '1.0.0' );
//wp_enqueue_style('xpayments_widget_css', plugins_url( 'assets/css/widget.css', __FILE__ ));

        $account = $this->get_option( 'account' );
        $widgetKey = $this->get_option( 'widget_key' );
        $total = $this->get_order_total();
        $currency = get_woocommerce_currency();


        $customerId = WC()->customer->get_meta('xpayments_customer_id') ?: '';
        $showSaveCard = WC()->customer->get_id() ? 'true' : 'false';

        echo <<<HTML
<script>
function blockForm()
{
    var form = jQuery('form.checkout');

    if ( 1 !== form.data('blockUI.isBlocked') ) {
        form.block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });
    }
}
function unblockForm()
{
    var form = jQuery('form.checkout');

    if ( 1 === form.data('blockUI.isBlocked') ) {
        form.unblock();
    }
}

    document.addEventListener('DOMContentLoaded', function() {
// Put this in a document ready event
jQuery( document ).ajaxComplete(function( event, xhr, settings ) {
 
  if( settings.url.indexOf('update_order_review') > -1 ) {

     xpSuccess = false;
    if ('undefined' == typeof window.xpaymentsWidget) {
        window.xpaymentsWidget = new XPaymentsWidget();
        window.xpaymentsWidget.init({
            debug: true,
            account: '$account',
            widgetKey: '$widgetKey',
            container: '#xpayments-container',
            form: 'form.checkout',
            showSaveCard: '$showSaveCard',
            customerId: '$customerId',
            order: {
                currency: '$currency',
                total: '$total'
            },
        }).on('success', function (params) {
            var formElm = this.getFormElm();
            if (formElm) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'xpaymentsToken';
                input.value = params.token;
                formElm.appendChild(input);
                xpSuccess = true;
                jQuery(formElm).submit();
                xpSuccess = false;
            }
        }).on('formSubmit', function (e) {
            if (!xpSuccess) {
                blockForm();
                this.submit();
                e.preventDefault();
            }
        }).on('fail', function() {
            unblockForm();
        });
        
        jQuery('form.checkout').on('checkout_place_order_xpayments_cloud', function() {
            return xpSuccess;
        });
        jQuery( document.body ).on('updated_checkout', function(data) {
// why not triggering?
        });
        
    }
        window.xpaymentsWidget.load();
        
  }
 
});
    });
</script>
<div id="xpayments-container"></div>
HTML;


    }

    public function process_payment($order_id) {


        $api = $this->initClient();

        $token = stripslashes($_POST['xpaymentsToken']);

        $order = wc_get_order( $order_id );
        $user = $order->get_user();

        try {
            $response = $api->doPay(
                $token,
                $order->get_order_number(),
                $user->get_meta('xpayments_customer_id') ?: '',
                $this->prepareCart($order),
                'https://local.dev',
                'https://local.dev'

//                $this->getReturnURL(null, true),
//                $this->getCallbackURL(null, true)
            );

            $payment = $response->getPayment();
            $status = $payment->status;
            $note = $payment->message;

//            $this->processXpaymentsFraudCheckData($this->transaction, $payment);

            if (!is_null($response->redirectUrl)) {
                // Should redirect to continue payment
//                $this->transaction->setXpaymentsId($payment->xpid);
                $result = array(
                    'result'   => 'success',
                    'redirect' => $response->redirectUrl,
                );

            } else {

                $result = $this->processPaymentFinish($order, $payment);
                /*
                if (static::FAILED == $result) {
                    TopMessage::addError($note);
                }
*/
                $order->payment_complete();
                WC()->cart->empty_cart();
                $result = array(
                    'result'   => 'success',
                    'redirect' => $this->get_return_url( $order ),
                );
            }

        } catch (\XPaymentsCloud\ApiException $exception) {
            $result = array();
//            $note = $exception->getMessage();
//            $this->log('Error: ' . $note);
            $message = $exception->getPublicMessage();
            if (!$message) {
                $message = 'Failed to process the payment!';
            }
            wc_add_notice( __('Payment error:', 'woothemes') . ' ' . $message, 'error' );
            //WC()->session->reload_checkout = 1;

        }

        return $result;
    }

    /**
     * Prepare shopping cart data
     *
     * @return array
     */
    public function prepareCart(WC_Order $order)
    {
        /**
        $payment_via      = $order->get_payment_method_title();
        $payment_method   = $order->get_payment_method();
        $payment_gateways = WC()->payment_gateways() ? WC()->payment_gateways->payment_gateways() : array();
        $transaction_id   = $order->get_transaction_id();

        if ( $transaction_id ) {

        $url = isset( $payment_gateways[ $payment_method ] ) ? $payment_gateways[ $payment_method ]->get_transaction_url( $order ) : false;

        if ( $url ) {
        $payment_via .= ' (<a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $transaction_id ) . '</a>)';
        } else {
        $payment_via .= ' (' . esc_html( $transaction_id ) . ')';
        }
        }


        return apply_filters(
        'woocommerce_admin_order_preview_get_order_details',
        array(
        'data'                       => $order->get_data(),
        'order_number'               => $order->get_order_number(),
        'item_html'                  => self::get_order_preview_item_html( $order ),
        'actions_html'               => self::get_order_preview_actions_html( $order ),
        'ship_to_billing'            => wc_ship_to_billing_address_only(),
        'needs_shipping'             => $order->needs_shipping_address(),
        'formatted_billing_address'  => $billing_address ? $billing_address : __( 'N/A', 'woocommerce' ),
        'formatted_shipping_address' => $shipping_address ? $shipping_address : __( 'N/A', 'woocommerce' ),
        'shipping_address_map_url'   => $order->get_shipping_address_map_url(),
        'payment_via'                => $payment_via,
        'shipping_via'               => $order->get_shipping_method(),
        'status'                     => $order->get_status(),
        'status_name'                => wc_get_order_status_name( $order->get_status() ),
        ),
        $order
        );
         */

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
     * @return string
     */
    private function processPaymentFinish(WC_Order $order, XpPayment $payment)
    {

        if ($payment->customerId) {
            update_user_meta($order->get_user_id(), 'xpayments_customer_id', $payment->customerId);
        }
        /*
        $this->setTransactionDataCells($transaction, $payment);

        if ($payment->initialTransactionId) {
            $transaction->setPublicId($payment->initialTransactionId . ' (' . $transaction->getPublicId() . ')');
        }

        if ($payment->customerId) {
            $transaction->getOrigProfile()->setXpaymentsCustomerId($payment->customerId);
        }

        $status = $payment->status;

        if (
            XpPayment::AUTH == $status
            || XpPayment::CHARGED == $status
        ) {
            $result = static::COMPLETED;
            $this->setTransactionTypeByStatus($transaction, $status);

        } elseif (
            XpPayment::DECLINED == $status
        ) {
            $result = static::FAILED;

        } else {
            $result = static::PENDING;
        }

        return $result;
        */
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
