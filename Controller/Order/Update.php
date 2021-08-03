<?php

namespace Dibs\EasyCheckout\Controller\Order;

use Dibs\EasyCheckout\Model\CheckoutException;

abstract class Update extends \Dibs\EasyCheckout\Controller\Checkout
{
    //ajax updates
    protected function _sendResponse($blocks = null, $updateCheckout = true)
    {
        $response = [];

        //reload the blocks even we have an error
        if (is_null($blocks)) {
            $blocks = ['shipping_method','cart','coupon','messages', 'dibs','newsletter','grand_total'];
        } elseif ($blocks) {
            $blocks = (array)$blocks;
        } else {
            $blocks = [];
        }

        if (!in_array('messages', $blocks)) {
            $blocks[] = 'messages';
        }

        $shouldUpdateDibs = false;
        if ($updateCheckout) {
            $key = array_search('dibs', $blocks);
            if ($key !== false) {
                $shouldUpdateDibs = true;
                unset($blocks[$key]); //this will be set later
            }
        }

        $checkout = $this->getDibsCheckout();
        $checkout->setCheckoutContext($this->dibsCheckoutContext);

        if ($updateCheckout) {  //if blocks contains only "messages" do not update
            $dibsPaymentId = null;
            try {
                $checkout = $checkout->initCheckout();

                //set new quote signature
                $response['ctrlkey'] = $checkout->getQuoteSignature();

                if ($shouldUpdateDibs) {
                    //update dibs iframe
                    $dibsPaymentId = $this->getCheckoutSession()->getDibsPaymentId();

                    if (!$dibsPaymentId) {
                        throw new CheckoutException(__("The session has expired."), '*/*');
                    }
                    $checkout->updateDibsPayment($dibsPaymentId);
                }
            } catch (CheckoutException $e) {
                $this->messageManager->addExceptionMessage(
                    $e,
                    $e->getMessage()
                );
                if ($e->isReload()) {
                    $response['reload'] = 1;
                    $response['messages'] = $e->getMessage();
                    $this->messageManager->addNoticeMessage($e->getMessage());
                } elseif ($e->getRedirect()) {
                    $response['redirect'] = $e->getRedirect();
                    $response['messages'] = $e->getMessage();
                    $this->messageManager->addErrorMessage($e->getMessage());
                } else {
                    $this->messageManager->addErrorMessage($e->getMessage());
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                //do nothing, we will just show the message
                $this->messageManager->addErrorMessage($e->getMessage() ? $e->getMessage() : __('Cannot update checkout (%1)', get_class($e)));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage() ? $e->getMessage() : __('Cannot initialize Dibs Checkout (%1)', get_class($e)));
            }

            if (!empty($response['redirect'])) {
                if ($this->getRequest()->isXmlHttpRequest()) {
                    $response['redirect'] = $this->storeManager->getStore()->getUrl($response['redirect']);
                    $this->getResponse()->setBody(json_encode($response));
                } else {
                    $this->_redirect($response['redirect']);
                }
                return;
            }

            /*
            if($shouldUpdateDibs &&  (empty($updatedDibsPaymentId) || $updatedDibsPaymentId != $dibsPaymentId)) {
                //another dibs order was created, add dibs block (need to be reloaded)
                $blocks[] = 'dibs';
                //if dibs have same location, we will use dibs api resume
            }
            */
        }

        $response['ok'] = true; //to avoid empty response

        if (!$this->getRequest()->isXmlHttpRequest()) {
            $this->_redirect('*');
            return;
        }

        $response['ok'] = true;
        if ($blocks) {
            $page = $this->resultPageFactory->create();
            $page->addHandle('dibs_easy_checkout_order_update');
            $page->getLayout()->getUpdate()->load();
            foreach ($blocks as $id) {
                $name = "dibs_easy_checkout.{$id}";
                $block = $page->getLayout()->getBlock($name);
                if ($block) {
                    $response['updates'][$id] = $block->toHtml();
                }
            }
        }
        $this->getResponse()->setBody(json_encode($response));
    }
}
