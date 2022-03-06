define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/set-payment-information',
        'mage/url'
    ],
    function (
        $,
        Component,
        additionalValidators,
        setPaymentInformationAction,
        url
        ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'OAG_Redsys/payment/redsys',
                code: 'oag_redsys'
            },
            redirectAfterPlaceOrder: false,
            /**
             * Place order.
             */
            placeOrder: function (data, event) {
                var self = this;

                if (event) {
                    event.preventDefault();
                }

                if (this.validate() &&
                    additionalValidators.validate() &&
                    this.isPlaceOrderActionAllowed() === true
                ) {
                    this.isPlaceOrderActionAllowed(false);

                    $.when(
                        setPaymentInformationAction(
                            this.messageContainer,
                            this.getData()
                        )
                    ).done( function () {
                        window.location.replace(
                            url.build(window.checkoutConfig.payment.oag_redsys.redirectUrl)
                        );
                    }).always( function () {
                        self.isPlaceOrderActionAllowed(true);
                    });

                    return true;
                }

                return false;
            }
        });
    }
);
