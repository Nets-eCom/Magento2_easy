define(
    [
        'Nexi_Checkout/js/sdk/loader',
        'Nexi_Checkout/js/view/payment/initialize-payment',
        'mage/url'
    ],
    function (sdkLoader, initializeCartPayment, url) {
        'use strict';

        return async function () {
            try {
                await sdkLoader.loadSdk(this.config.environment === 'test');

                //clear checkout container
                document.getElementById("nexi-checkout-container").innerHTML = "";

                const response = await initializeCartPayment.call(this);
                if (response.paymentId) {
                    let checkoutOptions = {
                        checkoutKey: response.checkoutKey,
                        paymentId: response.paymentId,
                        containerId: "nexi-checkout-container",
                        language: "en-GB",
                        theme: {
                            buttonRadius: "5px"
                        }
                    };
                    this.dibsCheckout(new Dibs.Checkout(checkoutOptions));

                    // add this as a global variable for debugging
                    window.dibsCheckout = this.dibsCheckout;
                    console.log('DEBUG: Dibs Checkout initialized as global `window.dibsCheckout` :', this.dibsCheckout());

                    this.dibsCheckout().on('payment-completed', async function () {
                        window.location.href = url.build('checkout/onepage/success');
                    }.bind(this));

                    this.dibsCheckout().on('pay-initialized', async function (paymentId) {
                        //TODO: validate with backend
                        await this.placeOrder();
                        console.log('DEBUG: Payment initialized with ID:', paymentId);
                        this.dibsCheckout().send('payment-order-finalized', true);
                    }.bind(this));
                }
            } catch (error) {
                console.error('Error loading Nexi SDK or initializing payment:', error);
            }
        };
    }
);
