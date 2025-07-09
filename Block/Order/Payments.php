<?php
declare(strict_types=1);

namespace Nexi\Checkout\Block\Order;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Theme\Block\Html\Pager;
use Magento\Vault\Model\PaymentTokenRepository;
use Nexi\Checkout\Api\Data\SubscriptionInterface;
use Nexi\Checkout\Gateway\Config\Config;
use Nexi\Checkout\Model\Subscription\TotalConfigProvider;
use Nexi\Checkout\Model\ResourceModel\Subscription\Collection as SubscriptionCollection;
use Nexi\Checkout\Model\ResourceModel\Subscription\CollectionFactory;

class Payments extends Template
{
    /**
     * @var string
     */
    protected $_template = 'Nexi_Checkout::order/payments.phtml';

    /**
     * Payments constructor.
     *
     * @param Context $context
     * @param CollectionFactory $subscriptionCollectionFactory
     * @param Session $customerSession
     * @param StoreManagerInterface $storeManager
     * @param PaymentTokenRepository $paymentTokenRepository
     * @param SerializerInterface $serializer
     * @param TotalConfigProvider $totalConfigProvider
     * @param Config $config
     * @param CheckoutSession $checkoutSession
     * @param array $data
     */
    public function __construct(
        Context                                 $context,
        private readonly CollectionFactory      $subscriptionCollectionFactory,
        private readonly Session                $customerSession,
        private readonly StoreManagerInterface  $storeManager,
        private readonly PaymentTokenRepository $paymentTokenRepository,
        private readonly SerializerInterface    $serializer,
        private readonly TotalConfigProvider    $totalConfigProvider,
        private readonly Config                 $config,
        private readonly CheckoutSession        $checkoutSession,
        array                                   $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Payments protected constructor.
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->pageConfig->getTitle()->set(__('My Subscriptions'));
    }

    /**
     * Is Subscriptions functionality is enabled.
     *
     * @return bool
     */
    public function isSubscriptionsEnabled(): bool
    {
        return $this->totalConfigProvider->isSubscriptionsEnabled();
    }

    /**
     * Get recurring payments (subscriptions).
     *
     * @return SubscriptionCollection
     */
    public function getRecurringPayments()
    {
        $collection = $this->subscriptionCollectionFactory->create();
        $collection->addFieldToFilter('main_table.status', ['active', 'pending_payment', 'failed', 'rescheduled']);

        $collection->getSelect()->join(
            ['link' => 'nexi_subscription_link'],
            'main_table.entity_id = link.subscription_id'
        )->columns('MAX(link.order_id) as max_id')
            ->group('link.subscription_id');

        $collection->getSelect()->join(
            ['so' => 'sales_order'],
            'link.order_id = so.entity_id',
            ['main_table.entity_id', 'so.base_grand_total']
        );
        $collection->getSelect()->join(
            ['rpp' => 'recurring_payment_profiles'],
            'main_table.recurring_profile_id = rpp.profile_id',
            'name'
        );

        $collection->addFieldToFilter('main_table.customer_id', $this->customerSession->getId());

        return $collection;
    }

    /**
     * Get closed subscriptions.
     *
     * @return SubscriptionCollection
     */
    public function getClosedSubscriptions()
    {
        $collection = $this->subscriptionCollectionFactory->create();
        $collection->addFieldToFilter('main_table.status', SubscriptionInterface::STATUS_CLOSED);

        $collection->getSelect()->join(
            ['link' => 'nexi_subscription_link'],
            'main_table.entity_id = link.subscription_id'
        )->columns('MAX(link.order_id) as max_id')
            ->group('link.subscription_id');

        $collection->getSelect()->join(
            ['so' => 'sales_order'],
            'link.order_id = so.entity_id',
            ['main_table.entity_id', 'so.base_grand_total']
        );
        $collection->getSelect()->join(
            ['rpp' => 'recurring_payment_profiles'],
            'main_table.recurring_profile_id = rpp.profile_id',
            'name'
        );

        $collection->addFieldToFilter('main_table.customer_id', $this->customerSession->getId());

        return $collection;
    }

    /**
     * Validate date.
     *
     * @param string $date
     *
     * @return string
     */
    public function validateDate($date): string
    {
        $newDate = explode(' ', $date);
        return $newDate[0];
    }

    /**
     * Get recurring payment status name.
     *
     * @param string $recurringPaymentStatus
     *
     * @return Phrase|string
     */
    public function getRecurringPaymentStatusName(string $recurringPaymentStatus): Phrase|string
    {
        switch ($recurringPaymentStatus) {
            case 'active':
                return __('Active');
            case 'paid':
                return __('Paid');
            case 'failed':
                return __('Failed');
            case 'pending_payment':
                return __('Pending Payment');
            case 'rescheduled':
                return __('Rescheduled');
            case 'closed':
                return __('Closed');
        }
        return '';
    }

    /**
     * Get current currency.
     *
     * @return string
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getCurrentCurrency()
    {
        return $this->storeManager->getStore()->getCurrentCurrency()->getCurrencySymbol();
    }

    /**
     * Get view url.
     *
     * @param SubscriptionInterface $recurringPayment
     *
     * @return string
     */
    public function getViewUrl(SubscriptionInterface $recurringPayment)
    {
        return $this->getUrl('sales/order/view', ['order_id' => $recurringPayment->getOrderId()]);
    }

    /**
     * Prepare layout.
     *
     * @return $this|Payments
     * @throws LocalizedException
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if ($this->getRecurringPayments()) {
            $pager = $this->getLayout()->createBlock(
                Pager::class,
                'checkout.order.recurring.payments.pager'
            )->setCollection(
                $this->getRecurringPayments()
            );
            $this->setChild('pager', $pager);
            $this->getRecurringPayments()->load();
        }
        return $this;
    }

    /**
     * Get pager html.
     *
     * @return string
     */
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }

    /**
     * Get stop payment url.
     *
     * @param SubscriptionInterface $recurringPayment
     *
     * @return string
     */
    public function getStopPaymentUrl(SubscriptionInterface $recurringPayment)
    {
        return $this->getUrl('nexi/payments/stop', ['payment_id' => $recurringPayment->getId()]);
    }

    /**
     * Get empty recurring payment message.
     *
     * @return Phrase
     */
    public function getEmptyRecurringPaymentsMessage(): Phrase
    {
        return __('You have no payments to display.');
    }

    /**
     * Get credit card number.
     *
     * @param SubscriptionInterface $recurringPayment
     *
     * @return string
     */
    public function getCardNumber(SubscriptionInterface $recurringPayment): string
    {
        $token = $this->paymentTokenRepository->getById($recurringPayment->getSelectedToken());
        if ($token) {
            $tokenDetails = $this->serializer->unserialize($token->getTokenDetails());
            return '**** **** **** ' . $tokenDetails['maskedCC'];
        }

        return '';
    }

    /**
     * Get add_card request redirect url.
     *
     * @return string|null
     */
    public function getAddCardRedirectUrl(): ?string
    {
        return $this->config->getAddCardRedirectUrl();
    }

    /**
     * Get previous error.
     *
     * @return Phrase|null
     */
    public function getPreviousError(): ?Phrase
    {
        if ($this->checkoutSession->getData('nexi_previous_error')) {
            $previousError = $this->checkoutSession
                ->getData('nexi_previous_error', 1);
        }

        return $previousError ?? null;
    }
}
