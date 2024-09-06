<?php

namespace Dagstuhl\SwhArchiveClient\ApiClient;

use Dagstuhl\SwhArchiveClient\ApiClient\DefaultConfig\Config;
use Dagstuhl\SwhArchiveClient\ApiClient\Internal\SwhWebApiResource;
use Dagstuhl\SwhArchiveClient\ApiClient\Internal\SwhWebApiResponse;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class SwhWebApiClient
{
    // use SwhApiLogs;

    const API_VERSION = 'api/1/';
    const LIVE_API_URL = 'https://archive.softwareheritage.org';

    protected static ?self $currentClient = null;

    protected string $apiUrl;
    protected Client $client;
    protected array $clientOptions = [];
    protected array $requestOptions = [];
    protected ?Exception $exception = null;
    protected SwhWebApiResponse|null $lastResponse = null;

    public function __construct(string $apiUrl = null, string $token = null, array $clientOptions = null)
    {
        $this->apiUrl = $apiUrl;
        $this->apiUrl .= '/';
        $this->apiUrl = preg_replace('#/+$#', '/', $this->apiUrl); // guarantee one slash at the end of api url
        $this->apiUrl .= static::API_VERSION;

        $clientOptions = $clientOptions ?? Config::getDefaultOptions();
        $clientOptions['base_uri'] = $this->apiUrl;
        $this->client = new Client($clientOptions);
        $this->clientOptions = $clientOptions;

        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept'        => 'application/json',
        ];

        $this->requestOptions = [
            'headers' => $headers,
        ];
    }

    public static function getCurrent(): static
    {
        if (static::$currentClient === null) {
            static::$currentClient = new static(
                config('swh.web-api.api-url'),
                config('swh.web-api.token'),
                Config::getDefaultOptions()
            );
        }

        SwhWebApiResource::setApiClient(static::$currentClient);

        return static::$currentClient;
    }

    public function isLive(): bool
    {
        return $this->apiUrl === static::LIVE_API_URL;
    }

    public static function setCurrent(self $swhApiClient): void
    {
        self::$currentClient = $swhApiClient;
        SwhWebApiResource::setApiClient(static::$currentClient);
    }

    public function getResponse(string $method, string $requestPath, array $options = null): SwhWebApiResponse|null
    {
        $url = $this->apiUrl . $requestPath;

        try {
            $response = $this->client->request($method,$url, $options ?? $this->requestOptions);
            $response = new SwhWebApiResponse($response);
            $this->lastResponse = $response;
        }
        catch (GuzzleException $ex) {
            $this->exception = $ex;
            return null;
        }

        return $response;
    }

    public function getException(): ?Exception
    {
        return $this->exception;
    }

    public function getLastResponse(): SwhWebApiResponse|null
    {
        return $this->lastResponse;
    }
}