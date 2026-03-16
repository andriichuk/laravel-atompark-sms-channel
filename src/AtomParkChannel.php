<?php

declare(strict_types=1);

namespace Andriichuk\AtomParkSmsChannel;

use Illuminate\Notifications\Notification;
use InvalidArgumentException;

final readonly class AtomParkChannel
{
    public function __construct(
        private AtomParkClient $atomParkClient,
    ) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toAtomPark')) {
            throw new InvalidArgumentException(
                'Notification must implement toAtomPark() method.'
            );
        }

        $message = $notification->toAtomPark($notifiable);

        if (! $message instanceof Sms) {
            throw new InvalidArgumentException(
                'Notification::toAtomPark() must return an instance of '.Sms::class.'.'
            );
        }

        $data = $message->toArray();

        if (($data['phone'] ?? '') === '') {
            $data['phone'] = $this->resolvePhone($notifiable);
        }

        if ($data['phone'] === '') {
            throw new InvalidArgumentException(
                'Could not determine recipient phone number for AtomPark SMS notification.'
            );
        }

        $this->atomParkClient->sendSMS($data);
    }

    private function resolvePhone(object $notifiable): string
    {
        if (! method_exists($notifiable, 'routeNotificationFor')) {
            return (string) $notifiable;
        }

        $phone = $notifiable->routeNotificationFor('atompark');

        if ($phone === null) {
            $phone = $notifiable->routeNotificationFor(self::class);
        }

        return (string) ($phone ?? '');
    }
}
