<?php

use Andriichuk\AtomParkSmsChannel\AtomParkClient;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

it('throws when calling unknown AtomParkClient action', function () {
    $client = new AtomParkClient('sender', 'public', 'private', $this->createMock(HttpClient::class));

    $this->expectException(BadMethodCallException::class);

    /** @phpstan-ignore-next-line  */
    $client->unknownAction([]);
});

it('dispatches sendSMS with expected parameters', function () {
    $httpClient = $this->createMock(HttpClient::class);

    $httpClient->expects($this->once())
        ->method('post')
        ->with(
            'sendSMS',
            $this->callback(function (array $options): bool {
                expect($options)->toHaveKey(RequestOptions::FORM_PARAMS);

                $params = $options[RequestOptions::FORM_PARAMS];

                expect($params)->toMatchArray([
                    'text' => 'Hello',
                    'phone' => '+123456789',
                    'sender' => 'sender',
                    'key' => 'public',
                ]);

                expect($params)->toHaveKey('sum');

                return true;
            })
        )
        ->willReturn($this->createMock(ResponseInterface::class));

    $client = new AtomParkClient('sender', 'public', 'private', $httpClient);

    $client->sendSMS([
        'text' => 'Hello',
        'phone' => '+123456789',
    ]);
});
