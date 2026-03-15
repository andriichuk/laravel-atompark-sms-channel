<?php

use Andriichuk\AtomParkSmsChannel\AtomParkChannel;
use Andriichuk\AtomParkSmsChannel\AtomParkClient;
use Andriichuk\AtomParkSmsChannel\Sms;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Psr\Http\Message\ResponseInterface;

it('sends sms via AtomParkChannel for notifiable model', function () {
    $client = $this->createMock(AtomParkClient::class);

    $client->expects($this->once())
        ->method('sendSMS')
        ->with($this->callback(function (array $params): bool {
            expect($params)->toMatchArray([
                'text' => 'Test message',
                'phone' => '+123456789',
                'sms_lifetime' => 1,
            ]);

            return true;
        }))
        ->willReturn($this->createMock(ResponseInterface::class));

    $channel = new AtomParkChannel($client);

    $notifiable = new class extends Model
    {
        use Notifiable;

        public string $phone = '+123456789';

        public function routeNotificationForAtomPark(): string
        {
            return $this->phone;
        }
    };

    $notification = new class extends Notification
    {
        public function via($notifiable): array
        {
            return ['atompark'];
        }

        public function toAtomPark($notifiable): Sms
        {
            return new Sms(text: 'Test message');
        }
    };

    $channel->send($notifiable, $notification);
});

it('sends sms for anonymous notifiable route', function () {
    $client = $this->createMock(AtomParkClient::class);

    $client->expects($this->once())
        ->method('sendSMS')
        ->with($this->callback(function (array $params): bool {
            expect($params)->toMatchArray([
                'text' => 'Anonymous message',
                'phone' => '+380991112233',
                'sms_lifetime' => 1,
            ]);

            return true;
        }))
        ->willReturn($this->createMock(ResponseInterface::class));

    $this->app->instance(AtomParkClient::class, $client);

    NotificationFacade::route('atompark', '+380991112233')
        ->notify(new class extends Notification
        {
            public function via($notifiable): array
            {
                return ['atompark'];
            }

            public function toAtomPark($notifiable): Sms
            {
                return new Sms(text: 'Anonymous message');
            }
        });
});

it('throws when notification does not implement toAtomPark', function () {
    $channel = new AtomParkChannel($this->createMock(AtomParkClient::class));

    $notifiable = new class extends Model
    {
        use Notifiable;
    };

    $notification = new class extends Notification
    {
        public function via($notifiable): array
        {
            return ['atompark'];
        }
    };

    $channel->send($notifiable, $notification);
})->throws(InvalidArgumentException::class, 'Notification must implement toAtomPark() method.');

it('throws when toAtomPark does not return Sms instance', function () {
    $channel = new AtomParkChannel($this->createMock(AtomParkClient::class));

    $notifiable = new class extends Model
    {
        use Notifiable;

        public function routeNotificationForAtomPark(): string
        {
            return '+123456789';
        }
    };

    $notification = new class extends Notification
    {
        public function via($notifiable): array
        {
            return ['atompark'];
        }

        public function toAtomPark($notifiable): string
        {
            return 'not an Sms instance';
        }
    };

    $channel->send($notifiable, $notification);
})->throws(InvalidArgumentException::class, 'Notification::toAtomPark() must return an instance of '.Sms::class.'.');

it('throws when phone cannot be resolved from notifiable', function () {
    $channel = new AtomParkChannel($this->createMock(AtomParkClient::class));

    $notifiable = new class extends Model
    {
        use Notifiable;

        public function routeNotificationForAtomPark(): string
        {
            return '';
        }
    };

    $notification = new class extends Notification
    {
        public function via($notifiable): array
        {
            return ['atompark'];
        }

        public function toAtomPark($notifiable): Sms
        {
            return new Sms(text: 'Test');
        }
    };

    $channel->send($notifiable, $notification);
})->throws(InvalidArgumentException::class, 'Could not determine recipient phone number for AtomPark SMS notification.');
