<?php

namespace Nexi\Checkout\Test\Unit\Gateway;

use Nexi\Checkout\Gateway\StringSanitizer;
use PHPUnit\Framework\TestCase;

class StringSanitizerTest extends TestCase
{
    /**
     * @var StringSanitizer
     */
    private $stringSanitizer;

    protected function setUp(): void
    {
        $this->stringSanitizer = new StringSanitizer();
    }

    /**
     * Test sanitizing strings with special characters
     *
     * @dataProvider specialCharactersDataProvider
     */
    public function testSanitizeSpecialCharacters(string $input, string $expected): void
    {
        $result = $this->stringSanitizer->sanitize($input);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test truncating strings that exceed the maximum length
     *
     * @dataProvider lengthDataProvider
     */
    public function testSanitizeTruncatesLongStrings(string $input, int $maxLength, string $expected): void
    {
        $result = $this->stringSanitizer->sanitize($input, $maxLength);
        $this->assertEquals($expected, $result);
        $this->assertLessThanOrEqual($maxLength, strlen($result));
    }

    /**
     * Test that strings shorter than the maximum length are not modified
     */
    public function testSanitizeDoesNotModifyShortStrings(): void
    {
        $input = 'This is a normal string with no special characters';
        $result = $this->stringSanitizer->sanitize($input);
        $this->assertEquals($input, $result);
    }

    /**
     * Data provider for testSanitizeSpecialCharacters
     *
     * @return array
     */
    public function specialCharactersDataProvider(): array
    {
        return [
            'angle brackets' => ['Test <tag>', 'Test -tag-'],
            'quotes' => ['Test "quoted" and \'single\'', 'Test -quoted- and -single-'],
            'ampersand' => ['Test & symbol', 'Test - symbol'],
            'backslash' => ['Test \\ backslash', 'Test - backslash'],
            'multiple special chars' => ['<Test & "string" with \'all\' special \\ chars>', '-Test - -string- with -all- special - chars-'],
        ];
    }

    /**
     * Data provider for testSanitizeTruncatesLongStrings
     *
     * @return array
     */
    public function lengthDataProvider(): array
    {
        return [
            'default max length' => [
                str_repeat('a', 150),
                128,
                str_repeat('a', 128)
            ],
            'custom max length' => [
                str_repeat('b', 50),
                30,
                str_repeat('b', 30)
            ],
            'exact max length' => [
                str_repeat('c', 20),
                20,
                str_repeat('c', 20)
            ],
            'shorter than max length' => [
                str_repeat('d', 10),
                20,
                str_repeat('d', 10)
            ],
        ];
    }
}
