# AtomPark SMS Notification Channel for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/andriichuk/laravel-atompark-sms-channel.svg?style=flat-square)](https://packagist.org/packages/andriichuk/laravel-atompark-sms-channel)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/andriichuk/laravel-atompark-sms-channel/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/andriichuk/laravel-atompark-sms-channel/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/andriichuk/laravel-atompark-sms-channel/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/andriichuk/laravel-atompark-sms-channel/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/andriichuk/laravel-atompark-sms-channel.svg?style=flat-square)](https://packagist.org/packages/andriichuk/laravel-atompark-sms-channel)

This package makes it easy to send SMS notifications using [AtomPark](https://www.atompark.com/bulk-sms-service/smsapiv3/) from your Laravel application, using Laravel's built-in notification system.

Sending an SMS to a user becomes as simple as using:

```php
$user->notify(new YourNotification());
```

### Contents

- Installation
  - Setting up the AtomPark service
- Usage
  - Sending text messages
    - Available message methods
- Testing
- Changelog
- Contributing
- Security
- License

## Installation

You can install the package via composer:

```bash
composer require andriichuk/laravel-atompark-sms-channel
```

The service provider will be auto-discovered by Laravel.

### Setting up the AtomPark service

Add your AtomPark SMS credentials to the `services.php` config file:

```php
// config/services.php

return [
    // ...

    'atompark' => [
        'sms' => [
            'sender' => env('ATOMPARK_SMS_SENDER'),
            'public_key' => env('ATOMPARK_SMS_PUBLIC_KEY'),
            'private_key' => env('ATOMPARK_SMS_PRIVATE_KEY'),
        ],
    ],
];
```

Then add the corresponding environment variables to your `.env`:

```bash
ATOMPARK_SMS_SENDER="Your Sender Name"
ATOMPARK_SMS_PUBLIC_KEY="your-public-key"
ATOMPARK_SMS_PRIVATE_KEY="your-private-key"
```

## Usage

### Notifiable model

In your notifiable model (typically `User`), add the `routeNotificationForAtomPark` method that returns a full mobile number including country code:

```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    public function routeNotificationForAtomPark(): string
    {
        return $this->phone; // e.g. +380991112233
    }
}
```

### Notification class

Within your notification, add the AtomPark channel to the `via` method and implement `toAtomPark` to build the SMS message:

```php
use Andriichuk\AtomParkSmsChannel\AtomParkChannel;
use Andriichuk\AtomParkSmsChannel\Sms;
use Illuminate\Notifications\Notification;

class Invitation extends Notification
{
    public function via($notifiable): array
    {
        return ['atompark'];
        // or: return [AtomParkChannel::class];
    }

    public function toAtomPark($notifiable): Sms
    {
        return new Sms(
            text: 'You have been invited!',
            phone: $notifiable->routeNotificationForAtomPark(),
            lifetime: 1, // 0 = maximum, 1/6/12/24 hours
        );
    }
}
```

Now you can send an SMS notification to a user:

```php
$user->notify(new Invitation());
```

### Anonymous notifications

You can also send SMS messages to phone numbers that are not associated with a notifiable model:

```php
use Illuminate\Support\Facades\Notification;

Notification::route('atompark', '+380991112233')
    ->notify(new Invitation());
```

Your `toAtomPark` method will receive an `AnonymousNotifiable` instance, and you can resolve the phone number using:

```php
public function toAtomPark($notifiable): Sms
{
    $phone = method_exists($notifiable, 'routeNotificationFor')
        ? $notifiable->routeNotificationFor('atompark')
        : (string) $notifiable;

    return new Sms(
        text: 'You have been invited!',
        phone: $phone,
    );
}
```

### Available message options

The `Sms` value object supports:

- `text` (string) – the message body.
- `phone` (string) – recipient phone number including country code.
- `lifetime` (int) – message lifetime in hours (`0` = maximum, `1`, `6`, `12`, `24`).

## Testing

Run the test suite with:

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Serhii Andriichuk](https://github.com/andriichuk)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
