<?php

declare(strict_types=1);

namespace Andriichuk\AtomParkSmsChannel;

use BadMethodCallException;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

/**
 * @see https://www.atompark.com/bulk-sms-service/smsapiv3/
 *
 * @method ResponseInterface sendSMS(array $parameters)
 * @method ResponseInterface getUserBalance(array $parameters)
 * @method ResponseInterface getCampaignInfo(array $parameters)
 */
class AtomParkClient
{
    private const string HOST = 'http://atompark.com/api/sms';

    private const string VERSION = '3.0';

    private array $actions = [
        'addAddressBook',
        'delAddressBook',
        'editAddressBook',
        'getAddressBook',
        'searchAddressBook',
        'cloneAddressBook',
        'addPhoneToAddressBook',
        'getPhoneFromAddressBook',
        'delPhoneFromAddressBook',
        'delPhoneFromAddressBookGroup',
        'editPhone',
        'searchPhones',
        'addPhoneToExceptions',
        'delPhoneFromExceptions',
        'editExceptions',
        'getException',
        'searchPhonesInExceptions',
        'getUserBalance',
        'registerSender',
        'getSenderStatus',
        'createCampaign',
        'sendSMS',
        'sendSMSGroup',
        'getCampaignInfo',
        'getCampaignDeliveryStats',
        'cancelCampaign',
        'deleteCampaign',
        'checkCampaignPrice',
        'checkCampaignPriceGroup',
        'getCampaignList',
        'getCampaignDeliveryStatsGroup',
        'getTaskInfo',
    ];

    private array $aliases = [
        'addAddressBook' => 'addAddressbook',
        'delAddressBook' => 'delAddressbook',
        'editAddressBook' => 'editAddressbook',
        'getAddressBook' => 'getAddressbook',
        'cloneAddressBook' => 'cloneaddressbook',
        'delPhoneFromAddressBookGroup' => 'delphonefromaddressbookgroup',
        'sendSMS' => 'sendsms',
        'sendSMSGroup' => 'sendsmsgroup',
        'getCampaignDeliveryStatsGroup' => 'getcampaigndeliverystatsgroup',
        'getTaskInfo' => 'gettaskinfo',
    ];

    private Client $httpClient;

    public function __construct(
        private readonly string $senderName,
        private readonly string $publicKey,
        private readonly string $privateKey,
        ?Client $httpClient = null,
    ) {
        $this->httpClient = $httpClient ?? new Client([
            'base_uri' => self::HOST.'/'.self::VERSION.'/',
        ]);
    }

    public function __call(string $method, array $parameters = []): ResponseInterface
    {
        $parameters = array_key_exists(0, $parameters) ? $parameters[0] : [];
        $parameters = is_array($parameters) ? $parameters : [];
        $action = $this->action($method);

        if (! is_null($action)) {
            return self::dispatch($action, $parameters);
        }

        throw new BadMethodCallException("Method {$method} does not exist.");
    }

    public function sendSMS(array $parameters): ResponseInterface
    {
        return $this->dispatch('sendSMS', $parameters);
    }

    private function action(string $action): ?string
    {
        $action = in_array($action, $this->actions) ? $action : null;

        return array_key_exists($action, $this->aliases) ? $this->aliases[$action] : $action;
    }

    public function dispatch($action, array $parameters = []): ResponseInterface
    {
        $parameters['sender'] = $this->senderName;
        $parameters['key'] = $this->publicKey;
        $parameters['sum'] = $this->summary(array_merge($parameters, [
            'action' => $action,
            'version' => self::VERSION,
        ]));

        return $this->httpClient->post($action, [
            RequestOptions::FORM_PARAMS => $parameters,
        ]);
    }

    /**
     * Generate control summary.
     */
    private function summary(array $parameters = []): string
    {
        ksort($parameters);

        $summary = implode('', $parameters);
        $summary .= $this->privateKey;

        return md5($summary);
    }
}
