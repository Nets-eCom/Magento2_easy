<?php

namespace Dibs\EasyCheckout\Controller\Order;


class SaveShippingMethod extends \Dibs\EasyCheckout\Controller\Order\Update
{

    /**
     * Save shipping method action
     */

    public function execute()
    {
        if ($this->ajaxRequestAllowed()) {
            return;
        }

        $shippingMethod = $this->getRequest()->getPost('shipping_method', '');
        if(!$shippingMethod) {
            $this->getResponse()->setBody(json_encode(array('messages'=>'Please choose a valid shipping method.')));
            return;
        }


        if($shippingMethod) {
            try {
                $checkout = $this->getDibsCheckout();
                $checkout->updateShippingMethod($shippingMethod);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addExceptionMessage(
                    $e,
                    $e->getMessage()
                );
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage(
                    $e,
                    __('We can\'t update shipping method.')
                );
            }
        }
        $this->_sendResponse(['cart','coupon','messages', 'dibs','newsletter','comment','country']);
    }

}

