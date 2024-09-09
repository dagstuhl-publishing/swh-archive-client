<?php

namespace Dagstuhl\SwhArchiveClient\SwhObjects;

use Dagstuhl\SwhArchiveClient\ApiClient\Internal\FillFromApiData;
use Dagstuhl\SwhArchiveClient\ApiClient\Internal\SwhWebApiResource;

class Counter extends SwhWebApiResource
{
    use FillFromApiData;
    const ENDPOINT_COUNTERS = 'stat/counters/';

    public readonly int $content;
    public readonly int $directory;
    public readonly int $origin;
    public readonly int $originVisit;
    public readonly int $person;
    public readonly int $release;
    public readonly int $revision;
    public readonly int $skippedContent;
    public readonly int $snapshot;


    public function __construct(array $apiData)
    {
        $this->fillFromApiData($apiData);
    }

    public static function getCurrent(): ?static
    {
        $response = self::getApiClient()->getResponse('GET', self::ENDPOINT_COUNTERS);

        if ($response !== null && $response->successful()) {
            $apiData = $response->json();
            return new static($apiData);
        }

        return null;
    }

}