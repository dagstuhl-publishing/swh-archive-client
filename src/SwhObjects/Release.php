<?php

namespace Dagstuhl\SwhArchiveClient\SwhObjects;

use Dagstuhl\SwhArchiveClient\ApiClient\Internal\FillFromApiData;
use Dagstuhl\SwhArchiveClient\ApiClient\Internal\SwhWebApiResource;
use Dagstuhl\SwhArchiveClient\ApiClient\SwhWebApiClient;

class Release extends SwhWebApiResource
{
    use FillFromApiData;
    const ENDPOINT_RELEASE = 'release/{release_id}/';

    public readonly string $id;
    public readonly string $name;
    public readonly string $target;
    public readonly string $targetType;
    protected ?Revision $revision = null;

    protected static array $apiPropRenaming = [
        'targetType' => 'target_type'
    ];

    public function __construct(array $apiData)
    {
        $this->fillFromApiData($apiData);
    }

    public static function byId(string $releaseId): ?static
    {
        $url = str_replace('{release_id}', $releaseId, self::ENDPOINT_RELEASE);
        $response = self::getApiClient()->getResponse('GET', $url);

        if ($response !== null && $response->successful()) {
            $apiData = $response->json();
            return new static($apiData);
        }

        return null;
    }

    public function getRevision(): ?Revision
    {
        if ($this->revision !== null) {
            return $this->revision;
        }

        if ($this->targetType === 'revision') {
            $this->revision = Revision::byId($this->target);
        }

        return $this->revision;
    }
}