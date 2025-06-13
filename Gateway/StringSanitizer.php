<?php

declare(strict_types=1);

namespace Nexi\Checkout\Gateway;

class StringSanitizer
{
    /**
     * Trims long strings to a maximum length that nexi api can handle
     *
     * Must be between 1 and 128 characters.
     * The following special characters are not supported: <, >, ', ", &, \
     *
     * @param string $string
     * @param int $maxLength
     *
     * @return string
     */
    public function sanitize(string $string, $maxLength = 128)
    {
        $string = preg_replace('/[<>\'"&\\\\]/', '-', $string);

        if (strlen($string) > $maxLength) {
            return substr($string, 0, $maxLength);
        }

        return $string;
    }
}
