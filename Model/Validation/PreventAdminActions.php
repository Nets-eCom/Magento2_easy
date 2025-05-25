<?php

namespace Nexi\Checkout\Model\Validation;

use Magento\Customer\Model\Session;

class PreventAdminActions
{
    const KEY_LOGIN_AS_CUSTOMER_SESSION = 'logged_as_customer_admind_id';

    /**
     * @var Session
     */
    private Session $customerSession;

    /**
     * @param Session $customerSession
     */
    public function __construct(
        Session $customerSession
    ) {
        $this->customerSession = $customerSession;
    }

    /**
     * @return bool
     */
    public function isAdminAsCustomer(): bool
    {
        if ($this->customerSession->getData(self::KEY_LOGIN_AS_CUSTOMER_SESSION)) {
            return true;
        }

        return false;
    }
}
