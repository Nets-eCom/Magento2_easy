<?php

namespace Dibs\EasyCheckout\Controller\Order;

use Dibs\EasyCheckout\Helper\Cart as DibsCartHelper;
use Dibs\EasyCheckout\Model\Checkout as DibsCheckout;
use Dibs\EasyCheckout\Model\CheckoutContext as DibsCheckoutCOntext;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Directory\Model\Country\Postcode\ValidatorInterface;

class CheckAddress extends Update
{

    public function execute()
    {
        $countryIso = (string)$this->getRequest()->getParam('countryCode'); // SWE, 3 letters
        $postalCode = (string)$this->getRequest()->getParam('postalCode');
        $postalCode = preg_replace("/[^0-9]/", "", $postalCode);

        $countryId = $countryIso; // TODO convert to country code


        /* // TODO
        if (!$this->validateCountryId($countryId)) {
            $this->getResponse()->setBody(json_encode(array('messages' => 'Please select a Valid Country.')));
            return;
        }
        */

        if (!$postalCode) {
            $this->getResponse()->setBody(json_encode(array('messages' => 'Please select a Postal Code.')));
            return;
        }


        try {
            $quote = $this->getDibsCheckout()->getQuote();
            $oldPostCode = $quote->getShippingAddress()->getPostcode();
            $oldCountryId = $quote->getShippingAddress()->getCountryId();

            // we do nothing
            if ($oldCountryId == $countryId && $oldPostCode == $postalCode) {
                $this->getResponse()->setBody(json_encode(array('chooseShippingMethod' => false)));
                return;
            }



            $quote->getShippingAddress()
                ->setPostcode($postalCode)
                ->setCountryId($countryId)
                ->setCollectShippingRates(true);

            $quote->getBillingAddress()
                ->setCountryId($countryId)
                ->setPostcode($postalCode);

            // save!
            $quote->save();
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                $e->getMessage()
            );
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('We can\'t update your Country / postal code.')
            );
        }


        $this->getResponse()->setBody(json_encode(array('chooseShippingMethod' => true, 'postalCode' => $postalCode, 'countryId' => $countryId)));
    }

    /*
    public function validateCountryId($countryId)
    {
        return (bool)in_array($countryId, $this->dibsCheckoutContext->getHelper()->getAllowedCountriesList());
    }
    */
}