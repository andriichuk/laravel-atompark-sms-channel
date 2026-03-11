<?php

declare(strict_types=1);

namespace Andriichuk\AtomParkSmsChannel;

use Illuminate\Notifications\Notification;

final readonly class AtomParkChannel
{
    public function __construct(
        private AtomParkClient $atomParkClient,
    ) {}

    public function send($notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toAtomPark')) {
            return;
        }

        $message = $notification->toAtomPark($notifiable);

        if (! $message instanceof Sms) {
            return;
        }

        $this->atomParkClient->sendSMS($message->toArray());
    }
}
