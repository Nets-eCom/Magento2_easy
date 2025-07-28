<?php

declare(strict_types=1);

namespace Nexi\Checkout\Model;

use Magento\Framework\Locale\ResolverInterface;

class Language
{
    /**
     * Default locale to use if the current locale is not supported
     */
    public const DEFAULT_LOCALE = 'en-GB';

    /**
     * List of supported locales for Nexi Checkout
     */
    private const SUPPORTED_LOCALES = [
        'en-GB', // English (United Kingdom)
        'da-DK', // Danish
        'nl-NL', // Dutch
        'ee-EE', // Estonian
        'fi-FI', // Finnish
        'fr-FR', // French
        'de-DE', // German
        'it-IT', // Italian
        'lv-LV', // Latvian
        'lt-LT', // Lithuanian
        'nb-NO', // Norwegian
        'pl-PL', // Polish
        'es-ES', // Spanish
        'sk-SK', // Slovak
        'sv-SE', // Swedish
    ];

    /**
     * @param ResolverInterface $localeResolver
     */
    public function __construct(
        private readonly ResolverInterface $localeResolver
    ) {
    }

    /**
     * Returns the locale code based on the provided locale.
     *
     * @return string The validated locale or the default locale if not supported
     */
    public function getCode(): string
    {
        $locale = str_replace('_', '-', $this->localeResolver->getLocale());

        return in_array($locale, self::SUPPORTED_LOCALES) ? $locale : self::DEFAULT_LOCALE;
    }
}
