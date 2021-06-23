<?php
namespace Dibs\EasyCheckout\Controller\Checkout;

use Dibs\EasyCheckout\Controller\Checkout;
use Dibs\EasyCheckout\Model\Client\DTO\Payment\CreatePaymentCheckout;
use Dibs\EasyCheckout\Api\CheckoutFlow;

class GetPaymentConfiguration extends Checkout
{
    const URL_CHECKOUT_CART_PATH = 'checkout/cart';
    const URL_CHECKOUT_PATH      = 'checkout';

    /**
     * @inheridoc
     */
    public function execute()
    {
        $checkout = $this->getDibsCheckout();
        $checkout->setCheckoutContext($this->dibsCheckoutContext);
        $vanillaParam = $this->getRequest()->getParam('vanilla', 0);
        $vanillaCheckout = (int)$vanillaParam === 1;
        $checkoutFlow = ($vanillaCheckout) ? CheckoutFlow::FLOW_VANILLA : 'custom';
        $integrationType = $this->dibsCheckoutContext->getHelper()->getCheckoutFlow();

        // We treat Vanilla as Embedded integration type here, and send checkoutFlow => Vanilla instead
        if ($integrationType === CheckoutFlow::FLOW_VANILLA) {
            $integrationType = CheckoutFlow::FLOW_EMBEDED;
        }

        $checkoutInfo = ['integrationType' => $integrationType, 'checkoutFlow' => $checkoutFlow];

        try {
            $checkout->initCheckout(false, !$vanillaCheckout);
            $dibsPayment = $checkout->initDibsCheckout($checkoutInfo, true);
        } catch (\Exception $e) {
            $checkout->getLogger()->critical($e);
            $this->getResponse()->setStatusCode(503);
            return;
        }

        $paymentResponse = [
            'checkoutKey' => $this->getDibsCheckoutKey(),
            'paymentId'   => $dibsPayment->getPaymentId(),
            'language'    => $this->getDibsCheckout()->getLocale(),
            'checkoutUrl' => $dibsPayment->getCheckoutUrl() . '&language=' .  $this->getDibsCheckout()->getLocale()
        ];

        $quote = $this->getDibsCheckout()->getQuote();
        $this->getDibsCheckout()->getHelper()->lockQuoteSignature($quote);
        $this->getResponse()->setBody(json_encode($paymentResponse));
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getDibsCheckoutKey()
    {
        $quote = $this->getCheckoutSession()->getQuote();
        return $this->getHelper()->getApiCheckoutKey($quote->getStoreId());
    }

    /**
     * @return \Dibs\EasyCheckout\Helper\Data
     */
    private function getHelper()
    {
        return $this->getDibsCheckout()->getHelper();
    }
}
