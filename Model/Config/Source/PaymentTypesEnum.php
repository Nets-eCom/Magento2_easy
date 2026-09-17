<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model\Config\Source;

enum PaymentTypesEnum: string
{
    // Credit card payment type
    case CARD = 'Card';

    // Other payment methods
    case PAYPAL = 'PayPal';
    case VIPPS = 'Vipps';
    case MOBILE_PAY = 'MobilePay';
    case SWISH = 'Swish';
    case ARVATO = 'Arvato';
    case EASY_INVOICE = 'EasyInvoice';
    case EASY_INSTALLMENT = 'EasyInstallment';
    case EASY_CAMPAIGN = 'EasyCampaign';
    case RATE_PAY_INVOICE = 'RatePayInvoice';
    case RATE_PAY_INSTALLMENT = 'RatePayInstallment';
    case RATE_PAY_SEPA = 'RatePaySepa';
    case TRUSTLY = 'Trustly';
    case APPLE_PAY = 'ApplePay';
    case KLARNA = 'Klarna';
    case GOOGLE_PAY = 'GooglePay';

    public function icon(): string
    {
        return match($this) {
            PaymentTypesEnum::CARD => 'nexi-cards.png',
            PaymentTypesEnum::PAYPAL => 'paypal.png',
            PaymentTypesEnum::VIPPS => 'vipps.png',
            PaymentTypesEnum::MOBILE_PAY => 'mobilepay.png',
            PaymentTypesEnum::SWISH => 'swish.png',
            PaymentTypesEnum::RATE_PAY_INVOICE => 'ratepay.png',
            PaymentTypesEnum::RATE_PAY_INSTALLMENT => 'ratepay.png',
            PaymentTypesEnum::RATE_PAY_SEPA => 'ratepay.png',
            PaymentTypesEnum::TRUSTLY => 'trustly.png',
            PaymentTypesEnum::APPLE_PAY => 'applepay.png',
            PaymentTypesEnum::KLARNA => 'klarna.png',
            PaymentTypesEnum::GOOGLE_PAY => 'googlepay.png',
            default => '',
        };
    }
}
