<?php
declare(strict_types=1);

namespace Nexi\Checkout\Block\Adminhtml\Subscription\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

abstract class AbstractButton implements ButtonProviderInterface
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * Constructor
     *
     * @param Context $context
     * @param RequestInterface $request
     */
    public function __construct(
        Context          $context,
        RequestInterface $request
    ) {
        $this->urlBuilder = $context->getUrlBuilder();
        $this->request = $request;
    }

    /**
     * Return the synonyms group Id.
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->request->getParam('id');
    }

    /**
     * Get the URL for the given route and parameters.
     *
     * @param string $route
     * @param array $params
     *
     * @return string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->urlBuilder->getUrl($route, $params);
    }

    /**
     * Retrieve button data
     *
     * @return array|null
     */
    abstract public function getButtonData();
}
