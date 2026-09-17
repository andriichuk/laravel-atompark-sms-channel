<?php

declare(strict_types=1);

namespace Andriichuk\AtomParkSmsChannel;

use Andriichuk\AtomParkSmsChannel\Exceptions\CouldNotSendNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

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

        $this->guardAgainstApiError(
            $this->atomParkClient->sendSMS($data),
            $data['phone'],
        );
    }

    /**
     * AtomPark reports failures as an {"error": ..., "code": ...} body under HTTP 200, so a send that
     * never left the gateway is indistinguishable from a delivered one unless the body is inspected.
     */
    private function guardAgainstApiError(ResponseInterface $response, string $phone): void
    {
        $body = (string) $response->getBody();
        $payload = json_decode($body, true);

        if (! is_array($payload)) {
            Log::error('AtomPark SMS send failed.', [
                'phone' => $this->maskPhone($phone),
                'status' => $response->getStatusCode(),
                'body' => $body,
            ]);

            throw CouldNotSendNotification::serviceRespondedWithMalformedBody($response->getStatusCode(), $body);
        }

        if (! isset($payload['error'])) {
            return;
        }

        $errorCode = (string) ($payload['code'] ?? '');
        $errorMessage = (string) $payload['error'];

        Log::error('AtomPark SMS send failed.', [
            'phone' => $this->maskPhone($phone),
            'code' => $errorCode,
            'error' => $errorMessage,
        ]);

        throw CouldNotSendNotification::serviceRespondedWithAnError($errorCode, $errorMessage);
    }

    private function maskPhone(string $phone): string
    {
        $visible = 4;

        if (strlen($phone) <= $visible) {
            return str_repeat('*', strlen($phone));
        }

        return str_repeat('*', strlen($phone) - $visible).substr($phone, -$visible);
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
