<?php


namespace Dibs\EasyCheckout\Controller;


use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;

use Dibs\EasyCheckout\Model\Dibs\Checkout as DibsCheckout;

abstract class Checkout extends \Magento\Checkout\Controller\Action
{

    /** @var DibsCheckout $dibsCheckout */
    protected $dibsCheckout;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        CustomerRepositoryInterface $customerRepository,
        AccountManagementInterface $accountManagement,
        DibsCheckout $dibsCheckout

    ) {
        $this->dibsCheckout = $dibsCheckout;

        parent::__construct(
            $context,
            $customerSession,
            $customerRepository,
            $accountManagement
        );
    }
}