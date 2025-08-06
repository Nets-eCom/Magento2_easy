<?php

namespace Nexi\Checkout\Gateway\Request;

enum PaymentTypesEnum: string
{
    // Credit card payment type
    case CREDIT_CARD_TYPE = 'Card';

    // Other payment types
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
    case SOFORT = 'Sofort';
    case TRUSTLY = 'Trustly';
    case APPLE_PAY = 'ApplePay';
    case KLARNA = 'Klarna';
    case GOOGLE_PAY = 'GooglePay';
}
