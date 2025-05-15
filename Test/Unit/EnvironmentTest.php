<?php

namespace Nexi\Checkout\Model\Config\Source;

use PHPUnit\Framework\TestCase;

class EnvironmentTest extends TestCase
{

    public function testToOptionArray()
    {
        $environment = new Environment();
        $this->assertEquals([
            ['value' => 'test', 'label' => __('Test')],
            ['value' => 'live', 'label' => __('Live')]
        ], $environment->toOptionArray());
    }
}
