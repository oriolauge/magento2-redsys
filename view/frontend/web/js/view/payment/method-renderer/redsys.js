define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/set-payment-information',
        'OAG_Redsys/js/action/get-redsys-payment-information',
        'Magento_Checkout/js/model/full-screen-loader',
        'OAG_Redsys/js/form-builder',
        'Magento_Customer/js/customer-data'
    ],
    function (
        $,
        Component,
        additionalValidators,
        setPaymentInformationAction,
        getRedsysPaymentInformationAction,
        fullScreenLoader,
        formBuilder,
        customerData
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
                        var deferred = $.Deferred();
                        fullScreenLoader.startLoader();
                        getRedsysPaymentInformationAction(deferred);
                        $.when(deferred).always(function () {
                            fullScreenLoader.stopLoader();
                        }).done(function(response) {
                            //Invalidate minicart cache
                            customerData.invalidate(['cart']);
                            //Create post form and redirect to redsys payment mage
                            formBuilder.build(
                                {
                                    action: window.checkoutConfig.payment.oag_redsys.postUrl,
                                    fields: JSON.parse(response)
                                }
                            ).submit();
                        });
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
