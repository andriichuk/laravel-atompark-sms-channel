<?php

use Andriichuk\AtomParkSmsChannel\AtomParkChannel;
use Andriichuk\AtomParkSmsChannel\AtomParkClient;
use Andriichuk\AtomParkSmsChannel\Exceptions\CouldNotSendNotification;
use Andriichuk\AtomParkSmsChannel\Sms;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;

function atomParkSuccessResponse(): Response
{
    return new Response(200, [], json_encode(['result' => ['id' => 1853174, 'price' => 0.13]]));
}

function atomParkResponse(array $payload): Response
{
    return new Response(200, [], json_encode($payload));
}

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
        ->willReturn(atomParkSuccessResponse());

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

it('resolves phone using full channel class name', function () {
    $client = $this->createMock(AtomParkClient::class);

    $client->expects($this->once())
        ->method('sendSMS')
        ->with($this->callback(function (array $params): bool {
            expect($params)->toMatchArray([
                'text' => 'Class based message',
                'phone' => '+987654321',
                'sms_lifetime' => 1,
            ]);

            return true;
        }))
        ->willReturn(atomParkSuccessResponse());

    $channel = new AtomParkChannel($client);

    $notifiable = new class extends Model
    {
        use Notifiable;

        public function routeNotificationFor($driver): ?string
        {
            return match ($driver) {
                'atompark' => null,
                AtomParkChannel::class => '+987654321',
                default => null,
            };
        }
    };

    $notification = new class extends Notification
    {
        public function via($notifiable): array
        {
            return [AtomParkChannel::class];
        }

        public function toAtomPark($notifiable): Sms
        {
            return new Sms(text: 'Class based message');
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
        ->willReturn(atomParkSuccessResponse());

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

it('logs and throws when AtomPark responds with an error payload', function () {
    Log::shouldReceive('error')
        ->once()
        ->withArgs(function (string $message, array $context): bool {
            expect($message)->toBe('AtomPark SMS send failed.')
                ->and($context)->toMatchArray([
                    'phone' => '******6789',
                    'code' => '-443',
                    'error' => 'Error sendSMS',
                ]);

            return true;
        });

    $client = $this->createMock(AtomParkClient::class);

    $client->expects($this->once())
        ->method('sendSMS')
        ->willReturn(atomParkResponse(['error' => 'Error sendSMS', 'code' => '-443', 'result' => '']));

    $channel = new AtomParkChannel($client);

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

        public function toAtomPark($notifiable): Sms
        {
            return new Sms(text: 'Test message');
        }
    };

    $channel->send($notifiable, $notification);
})->throws(CouldNotSendNotification::class, 'AtomPark responded with an error [-443]: Error sendSMS');

it('logs and throws when AtomPark responds with an unreadable body', function () {
    Log::shouldReceive('error')
        ->once()
        ->withArgs(fn (string $message, array $context): bool => $context['body'] === '<html>gateway down</html>');

    $client = $this->createMock(AtomParkClient::class);

    $client->expects($this->once())
        ->method('sendSMS')
        ->willReturn(new Response(200, [], '<html>gateway down</html>'));

    $channel = new AtomParkChannel($client);

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

        public function toAtomPark($notifiable): Sms
        {
            return new Sms(text: 'Test message');
        }
    };

    $channel->send($notifiable, $notification);
})->throws(CouldNotSendNotification::class, 'AtomPark returned an unreadable response [HTTP 200]');

it('does not log when AtomPark accepts the message', function () {
    Log::shouldReceive('error')->never();

    $client = $this->createMock(AtomParkClient::class);

    $client->expects($this->once())
        ->method('sendSMS')
        ->willReturn(atomParkSuccessResponse());

    $channel = new AtomParkChannel($client);

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

        public function toAtomPark($notifiable): Sms
        {
            return new Sms(text: 'Test message');
        }
    };

    $channel->send($notifiable, $notification);
});
