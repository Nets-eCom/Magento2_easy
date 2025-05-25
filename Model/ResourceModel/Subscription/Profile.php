<?php

namespace Nexi\Checkout\Model\ResourceModel\Subscription;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Profile extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('recurring_payment_profiles', 'profile_id');
    }

    protected function _beforeDelete(\Magento\Framework\Model\AbstractModel $object)
    {
        if (!$this->canDelete($object)) {
            throw new CouldNotDeleteException(__('Profiles that are in use by recurring payments cannot be deleted'));
        }

        return parent::_beforeDelete($object);
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return bool
     */
    private function canDelete(\Magento\Framework\Model\AbstractModel $object): bool
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from('nexi_subscriptions', ['entity_id', 'recurring_profile_id'])
            ->where('recurring_profile_id = ?', $object->getData('profile_id'));

        return empty($connection->fetchPairs($select));
    }
}
