<?php

namespace Dagstuhl\SwhArchiveClient\ApiClient;

use Carbon\Carbon;
use Dagstuhl\SwhArchiveClient\ApiClient\DefaultConfig\CacheStorage;
use Dagstuhl\SwhArchiveClient\ApiClient\DefaultConfig\Config;
use Dagstuhl\SwhArchiveClient\ApiClient\Internal\SwhWebApiResource;
use Dagstuhl\SwhArchiveClient\ApiClient\Internal\SwhWebApiResponse;
use Dagstuhl\SwhArchiveClient\SwhObjects\Counter;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Throwable;

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
            'Accept' => 'application/json'
        ];

        if ($token !== null) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        $this->requestOptions = [
            'headers' => $headers,
        ];
    }

    public static function getCurrent(): static
    {
        if (static::$currentClient === null) {
            static::$currentClient = new static(
                config('swh.web-api.url'),
                config('swh.web-api.token'),
                Config::getDefaultOptions()
            );

            SwhWebApiResource::setApiClient(static::$currentClient);
        }

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

    public function getRateLimit($ownRequest = true): ?array
    {
        $headers = null;

        if ($ownRequest && $this->lastResponse === null) {
            $dummyRequest = Counter::getCurrent();
        }

        if ($this->lastResponse !== null) {
            $headers = [];
            foreach($this->lastResponse->response->getHeaders() as $key=>$value) {
                if (str_starts_with($key, 'X-RateLimit')) {
                    $headers[$key] = $value;
                }
            }
        }

        return $headers;
    }

    public function clearCache(string $dateString = null, string $cacheDirectory = null): void
    {
        $localFileSystem = new LocalFilesystemAdapter($cacheDirectory ?? config('swh.web-api.cache-folder'));

        try {
            foreach($localFileSystem->listContents('/', 1) as $content) {
                if ($dateString === null || Carbon::createFromTimestampUTC($content->lastModified())->toDateString() === $dateString) {
                    if ($content->type() === 'file') {
                       $localFileSystem->delete($content->path());
                    }
                }
            }
        }
        catch (Throwable $ex) { }
    }
}