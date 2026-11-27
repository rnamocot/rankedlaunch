<?php

namespace Tests\Unit;

use App\Services\Pricing;
use PHPUnit\Framework\TestCase;

class PricingFormatTest extends TestCase
{
    public function test_prices_are_shown_without_needless_cents(): void
    {
        $this->assertSame('$20', Pricing::dollars(2000));
        $this->assertSame('$29', Pricing::dollars(2900));
        $this->assertSame('$0', Pricing::dollars(0));
        $this->assertSame('$19.50', Pricing::dollars(1950));
        $this->assertSame('$1,200', Pricing::dollars(120000));
    }
}
