<?php

namespace Tests\Unit;

use App\Support\RialAmountInput;
use Tests\TestCase;

class RialAmountInputTest extends TestCase
{
    public function test_formats_rial_from_toman(): void
    {
        $this->assertSame('20,000,000', RialAmountInput::formatRialFromToman(2_000_000));
    }

    public function test_parses_formatted_rial_to_toman(): void
    {
        $this->assertSame(2_000_000, RialAmountInput::tomanFromInput('20,000,000'));
    }

    public function test_sanitize_adds_separators(): void
    {
        $this->assertSame('2,000,000', RialAmountInput::sanitizeFormattedInput('2000000'));
    }
}
