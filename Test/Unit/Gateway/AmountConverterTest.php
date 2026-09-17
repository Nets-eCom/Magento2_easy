<?php

namespace Nexi\Checkout\Test\Unit\Gateway;

use InvalidArgumentException;
use Nexi\Checkout\Gateway\AmountConverter;
use PHPUnit\Framework\TestCase;

class AmountConverterTest extends TestCase
{
    /**
     * @var AmountConverter
     */
    private $amountConverter;

    protected function setUp(): void
    {
        $this->amountConverter = new AmountConverter();
    }

    /**
     * Test successful conversion of amount to Nexi format (cents)
     *
     * @dataProvider amountDataProvider
     */
    public function testConvertToNexiAmount($amount, $expected): void
    {
        $result = $this->amountConverter->convertToNexiAmount($amount);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test exception when non-numeric amount is provided
     */
    public function testConvertToNexiAmountWithInvalidInput(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be a numeric value.');
        $this->amountConverter->convertToNexiAmount('not-a-number');
    }

    /**
     * Data provider for testConvertToNexiAmount
     *
     * @return array
     */
    public function amountDataProvider(): array
    {
        return [
            'integer amount' => [100, 10000],
            'float amount' => [10.55, 1055],
            'zero amount' => [0, 0],
            'small decimal' => [0.01, 1],
            'rounding up' => [10.995, 1100],
            'rounding down' => [10.994, 1099],
            'string numeric' => ['10.55', 1055],
        ];
    }
}
