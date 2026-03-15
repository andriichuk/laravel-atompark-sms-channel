<?php

declare(strict_types=1);

namespace Andriichuk\AtomParkSmsChannel;

use Illuminate\Contracts\Support\Arrayable;

final readonly class Sms implements Arrayable
{
    public function __construct(
        public string $text,
        public ?string $phone = null,
        public int $lifetime = 1, // (0 = maximum, 1, 6, 12, 24 hours)
    ) {}

    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'phone' => $this->phone,
            'sms_lifetime' => $this->lifetime,
        ];
    }
}
