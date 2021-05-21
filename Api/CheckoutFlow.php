<?php declare(strict_types=1);

namespace Dibs\EasyCheckout\Api;

class CheckoutFlow
{
    public const FLOW_VANILLA  = 'Vanilla';
    public const FLOW_EMBEDED  = 'EmbeddedCheckout';
    public const FLOW_REDIRECT = 'HostedPaymentPage';
    public const FLOW_OVERLAY  = 'OverlayPayment';
}