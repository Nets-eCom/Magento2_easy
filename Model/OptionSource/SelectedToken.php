<?php
declare(strict_types=1);

namespace Nexi\Checkout\Model\OptionSource;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Vault\Model\PaymentTokenManagement;
use Nexi\Checkout\Api\SubscriptionLinkRepositoryInterface;
use Nexi\Checkout\Model\ResourceModel\Subscription\CollectionFactory;
use Magento\Sales\Model\OrderRepository;

class SelectedToken implements \Magento\Framework\Data\OptionSourceInterface
{
    private const MASKED_CC_VALUE = 'maskedCC';

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var Http
     */
    private $request;

    /**
     * @var PaymentTokenManagement
     */
    private $paymentTokenManagement;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var SubscriptionLinkRepositoryInterface
     */
    private $subscriptionLinkRepoInterface;

    /**
     * @param OrderRepository $orderRepository
     * @param Http $request
     * @param PaymentTokenManagement $paymentTokenManagement
     * @param SerializerInterface $serializer
     * @param SubscriptionLinkRepositoryInterface $subscriptionLinkRepoInterface
     */
    public function __construct(
        OrderRepository                     $orderRepository,
        Http                                $request,
        PaymentTokenManagement              $paymentTokenManagement,
        SerializerInterface                 $serializer,
        SubscriptionLinkRepositoryInterface $subscriptionLinkRepoInterface
    ) {
        $this->orderRepository               = $orderRepository;
        $this->request                       = $request;
        $this->paymentTokenManagement        = $paymentTokenManagement;
        $this->serializer                    = $serializer;
        $this->subscriptionLinkRepoInterface = $subscriptionLinkRepoInterface;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function toOptionArray()
    {
        $returnArray = [];
        $orderId     = $this->getOrderIdFromUrl();
        $customerId  = $this->getCustomerIdFromOrderId($orderId);
        foreach ($this->getVaultCardToken($customerId) as $paymentToken) {
            if ($paymentToken->getIsActive() && $paymentToken->getIsVisible()) {
                $returnArray[] = [
                    'value' => $paymentToken->getId(),
                    'label' => '**** **** **** ' . $this->serializer->unserialize(
                            $paymentToken->getTokenDetails()
                        )[self::MASKED_CC_VALUE]
                ];
            }
        }

        return $returnArray;
    }

    /**
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getOrderIdFromUrl()
    {
        $subscriptionId = (int)$this->request->getParams()['id'];
        $orderIds       = $this->subscriptionLinkRepoInterface->getOrderIdsBySubscriptionId($subscriptionId);

        return reset($orderIds);
    }

    /**
     * @param $orderId
     *
     * @return int|null
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCustomerIdFromOrderId($orderId)
    {
        $order = $this->orderRepository->get($orderId);

        return $order->getCustomerId();
    }

    /**
     * @param $customerId
     *
     * @return array
     */
    private function getVaultCardToken($customerId)
    {
        return $this->paymentTokenManagement->getListByCustomerId($customerId);
    }
}
