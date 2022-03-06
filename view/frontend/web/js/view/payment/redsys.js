define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';

        let config = window.checkoutConfig.payment;
        const redsysType = 'oag_redsys';

        if (config[redsysType].isActive) {
            rendererList.push(
                {
                    type: redsysType,
                    component: 'OAG_Redsys/js/view/payment/method-renderer/redsys'
                }
            );
        }

        /** Add view logic here if needed */
        return Component.extend({});
    }
);
