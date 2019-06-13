<?php

namespace Dibs\EasyCheckout\Controller\Order;

class SaveNewsletter extends \Dibs\EasyCheckout\Controller\Order\Update
{

    /**
     * Save newsletter subscription action
     */

    public function execute()
    {
        if ($this->ajaxRequestAllowed()) {
            return;
        }
        try {
            $quote = $this->getDibsCheckout()->getQuote();

            $newsletter = (int)$this->getRequest()->getParam('newsletter', 0);
            if ($quote->getPayment()) {
                $quote->getPayment()->setAdditionalInformation('dibs_easy_checkout_newsletter', $newsletter > 0 ? 1 : -1)->save();
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addExceptionMessage(
                    $e,
                    $e->getMessage()
                );
        } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage(
                    $e,
                    __('We can\'t update your subscription.')
                );
        }
        $this->_sendResponse('newsletter',$updateCheckout = false);
    }

}

