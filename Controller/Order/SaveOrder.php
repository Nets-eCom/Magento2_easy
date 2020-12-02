<?php

namespace Dibs\EasyCheckout\Controller\Order;

use Dibs\EasyCheckout\Controller\Checkout;

class SaveOrder extends Checkout
{
    /**
     * We don't save order here, because saving should
     * happen on webhook callback to prevent race condition
     *
     * @inheridoc
     */
    public function execute()
    {
        $paymentId  = $this->getRequest()->getParam('pid');
        if (! $paymentId) {
            return $this->respondWithError('Invalid payment id');
        }

        if (! $this->checkLockedQuoteSignature()) {
            return $this->respondWithError("Seems your has been modified after payment is complete. Your payment id: {$paymentId}");
        }

        return $this->respondWithPaymentId($paymentId);
    }

    /**
     * @param $paymentId
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    private function respondWithPaymentId($paymentId)
    {
        $helper = $this->dibsCheckoutContext->getHelper();
        $response = $this->getResponse();
        $response->setBody(\json_encode([
            'redirectTo' => $helper->getCheckoutUrl(null, ['paymentId' => $paymentId])
        ]));

        return $response;
    }

    /**
     * @return bool
     */
    private function checkLockedQuoteSignature() : bool
    {
        try {
            $quote = $this->getCheckoutSession()->getQuote();
        } catch (\Exception $e) {
            return false;
        }

        $helper             = $this->dibsCheckoutContext->getHelper();
        $quoteSignature     = $quote->getHashSignature();
        $currentSignature   = $helper->generateHashSignatureByQuote($quote);

        return $quoteSignature === $currentSignature;
    }

    /**
     * @param $message
     * @param false $redirectTo
     * @param array $extraData
     *
     * @return false
     */
    protected function respondWithError($message, $redirectTo = false, $extraData = [])
    {
        $data = ['messages' => $message, "redirectTo" => $redirectTo];
        $data = array_merge($data, $extraData);
        $this->getResponse()->setBody(json_encode($data));

        return false;
    }
}
