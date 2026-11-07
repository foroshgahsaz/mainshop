<?php

namespace Tests\Unit;

use App\Support\FilamentJalaliPickers;
use Tests\TestCase;

class FilamentJalaliPickersTest extends TestCase
{
    public function test_register_defaults_does_not_throw_when_package_missing(): void
    {
        FilamentJalaliPickers::registerDefaults();
        $this->addToAssertionCount(1);
    }
}
