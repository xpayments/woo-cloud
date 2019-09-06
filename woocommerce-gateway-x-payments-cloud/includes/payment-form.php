<?php
/**
 * Copyright (c) 2019-present Qualiteam software Ltd. All rights reserved.
 */

defined( 'ABSPATH' ) or die();

?>
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

    function xpTopMessage(message, type)
    {
        jQuery('.woocommerce-error, .woocommerce-message').remove();
        var form = jQuery('form.checkout');
        form.prepend(jQuery('<ul class="woocommerce-' + type + '"><li>' + message + '</li></ul>'));
        jQuery.scroll_to_notices(form);
        //   jQuery( 'html, body' ).animate( { scrollTop: @form.offset().top - 100 }, 1000 )
    }

    function loadXpaymentsWidget() {
        xpSuccess = false;
        if ('undefined' == typeof window.xpaymentsWidget) {
            window.xpaymentsWidget = new XPaymentsWidget();
            window.xpaymentsWidget.init({
                debug: true,
                account: '<?php echo $account; ?>',
                widgetKey: '<?php echo $widgetKey; ?>',
                container: '#xpayments-container',
                form: 'form.checkout',
                showSaveCard: '<?php echo $showSaveCard; ?>',
                customerId: '<?php echo $customerId; ?>',
                order: {
                    currency: '<?php echo $currency; ?>',
                    total: '<?php echo $total; ?>'
                },
            }).on('success', function (params) {
                var formElm = this.getFormElm();
                if (formElm) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = input.id = 'xpayments_token';
                    input.value = params.token;
                    formElm.appendChild(input);
                    xpSuccess = true;
                    jQuery(formElm).submit();
                    xpSuccess = false;
                }
            }).on('formSubmit', function (e) {
                if (!jQuery('#payment_method_<?php echo $paymentId; ?>').is(':checked')) {
                    // not XP payment method
                    return true;
                }
                if (
                    !xpSuccess
                    && 'undefined' !== typeof window.xpaymentsWidget
                    && window.xpaymentsWidget.isValid()
                ) {
                    blockForm();
                    this.submit();
                    e.preventDefault();
                }
            }).on('fail', function() {
                unblockForm();
            }).on('alert', function(params) {
                if ('popup' === params.type) {
                    alert(params.message);
                } else {
                    xpTopMessage(params.message, ('popup' === params.type ? 'message' : 'error'));
                }
            });

            jQuery('form.checkout').on('checkout_place_order_xpayments_cloud', function() {
                return xpSuccess;
            });
            jQuery('#payment_method_<?php echo $paymentId; ?>').click(function() {
                if (0 == jQuery('#xpayments-container iframe').height()) {
                    window.xpaymentsWidget.showSaveCard()
                }
            });

        }
        window.xpaymentsWidget.load();

    }

    document.addEventListener('DOMContentLoaded', function() {

        jQuery( document ).ajaxComplete(function( event, xhr, settings ) {

            if ( settings.url.indexOf('update_order_review') > -1 ) {
                loadXpaymentsWidget();
            } else if ( settings.url.indexOf('wc-ajax=checkout') > -1 ) {
                jQuery('#xpayments_token').remove();
            }

        });
    });
</script>
<div id="xpayments-container"></div>
