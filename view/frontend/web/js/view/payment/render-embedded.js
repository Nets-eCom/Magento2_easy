define([
  "Nexi_Checkout/js/sdk/loader",
  "Nexi_Checkout/js/view/payment/initialize-payment",
  "Nexi_Checkout/js/view/payment/validate",
  "mage/url",
  'Magento_Checkout/js/model/quote',
], function (sdkLoader, initializePayment, validatePayment, url, quote) {
  "use strict";

  // Define the rendering function
  return async function () {
     if (this.isRendering()) {
       console.log("Rendering already in progress. Skipping this call.");
       return;
     }

     // get selected payment method from the quote
     let selectedPaymentMethod = quote.paymentMethod();

     if (!selectedPaymentMethod || selectedPaymentMethod.method !== "nexi") {
       console.log("Selected payment method is not Nexi. Skipping rendering.");
       return;
     }

     this.isRendering(true);
     try {
       await sdkLoader.loadSdk(this.config.environment === "test");

       // Clear the container before rendering
       document.getElementById("nexi-checkout-container").innerHTML = "";

       const response = await initializePayment.call(this)
       if (response.paymentId) {
         let checkoutOptions = {
           checkoutKey: response.checkoutKey,
           paymentId: response.paymentId,
           containerId: "nexi-checkout-container",
           language: "en-GB",
           theme: {
             buttonRadius: "5px",
           },
         };
         this.dibsCheckout(new Dibs.Checkout(checkoutOptions));
         console.log("Nexi Checkout SDK loaded successfully. paymentId: ", response.paymentId);
       }
     } catch (error) {
       console.error("Error loading Nexi SDK or initializing payment:", error);
     } finally {
         this.isRendering(false);
     }
   }
});
