<?php

namespace App\Services\Payment;

class GatewayVerificationResult
{
    public function __construct(
        public bool $successful,
        public bool $canceled = false,
        public ?string $cardPan = null,
        public mixed $raw = null,
        public ?string $message = null,
        public ?int $paidAmount = null,
    ) {}

    public static function success(?string $cardPan = null, mixed $raw = null, ?int $paidAmount = null): self
    {
        return new self(
            successful: true,
            cardPan: $cardPan,
            raw: $raw,
            paidAmount: $paidAmount,
        );
    }

    public static function canceled(mixed $raw = null, ?string $message = null): self
    {
        return new self(successful: false, canceled: true, raw: $raw, message: $message);
    }

    public static function failed(mixed $raw = null, ?string $message = null): self
    {
        return new self(successful: false, raw: $raw, message: $message);
    }
}
