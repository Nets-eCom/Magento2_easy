<?php

namespace Dibs\EasyCheckout\Controller\Order;

use Dibs\EasyCheckout\Controller\Checkout;
use Magento\Quote\Model\Quote;

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

        try {
            $quote = $this->getCheckoutSession()->getQuote();
            // If hash signature is missing, it means, that order has been
            // already submitted during webhook (also signature is verified),
            // so we got new cleared quote - else we check locked signature
            // with calculated signature of current quote
            if ($quote->getHashSignature()) {
                $this->checkLockedQuoteSignature($quote);
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
            return $this->respondWithError("Your session has expired");
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage());
        }

        //if charge is set to yes create order and invoice dirctly
        $charge = $this->dibsCheckoutContext->getHelper()->getCharge($quote->getStoreId());
        if ($charge) {
            $checkout = $this->getDibsCheckout();
            $checkout->setCheckoutContext($this->dibsCheckoutContext);
            $this->dibsCheckout->tryToSaveDibsPayment($paymentId);
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
     * @param Quote $quote
     *
     * @throws \Exception
     */
    private function checkLockedQuoteSignature(Quote $quote)
    {
        $helper             = $this->dibsCheckoutContext->getHelper();
        $quoteSignature     = $quote->getHashSignature();
        $currentSignature   = $helper->generateHashSignatureByQuote($quote);

        if ($quoteSignature !== $currentSignature) {
            throw new \Exception("Seems your has been modified after payment is complete.");
        }
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
