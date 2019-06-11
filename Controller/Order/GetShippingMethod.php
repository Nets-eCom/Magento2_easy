<?php

namespace Dibs\EasyCheckout\Controller\Order;

use Dibs\EasyCheckout\Helper\Cart as DibsCartHelper;
use Dibs\EasyCheckout\Model\Checkout as DibsCheckout;
use Dibs\EasyCheckout\Model\CheckoutContext as DibsCheckoutCOntext;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Directory\Model\Country\Postcode\ValidatorInterface;

class GetShippingMethod extends Update
{

    /**
     * @var ValidatorInterface
     */
    protected $validatorInterface;

    /**
     * @var DibsCartHelper
     */
    protected $dibsCartHelper;

    /**
     * GetShippingMethod constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param AccountManagementInterface $accountManagement
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param DibsCheckout $dibsCheckout
     * @param DibsCheckoutCOntext $dibsCheckoutContext
     * @param ValidatorInterface $validatorInterface
     * @param DibsCartHelper $dibsCartHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        CustomerRepositoryInterface $customerRepository,
        AccountManagementInterface $accountManagement,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        DibsCheckout $dibsCheckout,
        DibsCheckoutCOntext $dibsCheckoutContext,
        ValidatorInterface $validatorInterface,
        DibsCartHelper $dibsCartHelper
    )
    {
        parent::__construct(
            $context,
            $customerSession,
            $customerRepository,
            $accountManagement,
            $checkoutSession,
            $storeManager,
            $resultPageFactory,
            $dibsCheckout,
            $dibsCheckoutContext
        );
        $this->validatorInterface = $validatorInterface;
        $this->dibsCartHelper = $dibsCartHelper;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $countryId = (string)$this->getRequest()->getParam('country_id');
        $postalCode = (string)$this->getRequest()->getParam('postal');

        $postalCode = preg_replace("/[^0-9]/", "", $postalCode);

        if (!$postalCode) {
            $this->getResponse()->setBody(json_encode(array('messages' => 'Please choose a valid Postal code.')));
            return;
        }
        if (!$this->validateCountryId($countryId)) {
            $this->getResponse()->setBody(json_encode(array('messages' => 'Please select a Valid Country.')));
            return;
        }

        /*
            if (!$this->validatePostalCode($countryId, $postalCode)) {
            $this->getResponse()->setBody(json_encode(array('messages' => "Postal code is not valid for " . $this->dibsCartHelper->getCountryNameByCode($countryId) . ".")));
            return;
        }*/

        if ($this->validateCountryId($countryId) && $postalCode) {
            try {
                $quote = $this->getDibsCheckout()->getQuote();
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
        }
        $this->_sendResponse(['shipping_method','cart','coupon','shipping','messages', 'dibs','newsletter','grand_total']);
    }

    public function validatePostalCode($countryId, $postalCode)
    {
        return (bool)$this->validatorInterface->validate($postalCode, $countryId);
    }

    public function validateCountryId($countryId)
    {
        return (bool)in_array($countryId, $this->dibsCartHelper->getAllowedCountriesList());
    }
}