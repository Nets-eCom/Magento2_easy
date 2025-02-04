<?php

namespace Nexi\Checkout\Gateway\Http;

use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Http\TransferInterface;

class TransferFactory implements TransferFactoryInterface
{

    /**
     * TransferFactory constructor.
     *
     * @param TransferBuilder $transferBuilder
     */
    public function __construct(
        private readonly TransferBuilder $transferBuilder
    ) {
    }

    /**
     * Builds gateway transfer object
     *
     * @param array $request
     *
     * @return TransferInterface
     */
    public function create(array $request): TransferInterface
    {
        $nexiMethod = $request['nexi_method'];
        unset($request['nexi_method']);
        return $this->transferBuilder
            ->setBody($request['body'])
            ->setUri($nexiMethod)
            ->build();
    }
}
