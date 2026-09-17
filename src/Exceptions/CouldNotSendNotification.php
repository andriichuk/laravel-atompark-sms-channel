<?php

declare(strict_types=1);

namespace Andriichuk\AtomParkSmsChannel\Exceptions;

use RuntimeException;

final class CouldNotSendNotification extends RuntimeException
{
    private function __construct(
        string $message,
        public readonly string $errorCode,
        public readonly string $errorMessage,
    ) {
        parent::__construct($message);
    }

    public static function serviceRespondedWithAnError(string $errorCode, string $errorMessage): self
    {
        return new self(
            sprintf('AtomPark responded with an error [%s]: %s', $errorCode, $errorMessage),
            $errorCode,
            $errorMessage,
        );
    }

    public static function serviceRespondedWithMalformedBody(int $statusCode, string $body): self
    {
        return new self(
            sprintf('AtomPark returned an unreadable response [HTTP %d]: %s', $statusCode, $body),
            (string) $statusCode,
            $body,
        );
    }
}
