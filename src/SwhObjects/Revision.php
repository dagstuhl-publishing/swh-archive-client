<?php

namespace Dagstuhl\SwhArchiveClient\SwhObjects;

use Dagstuhl\SwhArchiveClient\ApiClient\Internal\FillFromApiData;
use Dagstuhl\SwhArchiveClient\ApiClient\Internal\SwhWebApiResource;
use Dagstuhl\SwhArchiveClient\ApiClient\SwhWebApiClient;

class Revision extends SwhWebApiResource
{
    use FillFromApiData;
    const ENDPOINT_REVISION = 'revision/{revision_id}/';
    const ENDPOINT_REVISION_PATH = 'revision/{revision_id}/directory/{path}/';
    const ENDPOINT_REVISION_LOG = 'revision/{revision_id}/log/?limit={limit}';
    const LOG_LIMIT = 200;

    const ID_PREFIX = 'swh:1:rev:';

    public readonly string $id;
    public readonly string $directory;

    public function __construct(array $apiData)
    {
        $this->fillFromApiData($apiData);
    }

    public static function byId(string $revisionId): ?static
    {
        $revisionId = str_replace(static::ID_PREFIX, '', $revisionId);
        $url = str_replace('{revision_id}', $revisionId, self::ENDPOINT_REVISION);
        $response = self::getApiClient()->getResponse('GET', $url);

        if ($response !== null && $response->successful()) {
            $apiData = $response->json();
            return new static($apiData);
        }

        return null;
    }

    public function getContent(string $path): ?Content
    {
        $url = str_replace(['{revision_id}', '{path}'], [$this->id, $path], self::ENDPOINT_REVISION_PATH);
        $response = self::getApiClient()->getResponse('GET', $url);

        if ($response !== null && $response->successful()) {
            $apiData = $response->json();
            return new Content($apiData);
        }

        return null;
    }

    public function getIdentifier(): string
    {
        return self::ID_PREFIX . $this->id;
    }

    public function getDirectory(): Directory
    {
        return Directory::byId($this->directory);
    }

    public function getParentRevisionByHash(string $commitHash): ?Revision
    {
        $url = str_replace(['{revision_id}', '{limit}'], [$this->id, static::LOG_LIMIT], self::ENDPOINT_REVISION_LOG);
        $response = self::getApiClient()->getResponse('GET', $url);

        if ($response !== null && $response->successful()) {
            $apiData = $response->json();

            foreach($apiData as $commit) {
                if (str_starts_with($commit['id'], $commitHash)) {
                    return static::byId($commit['id']);
                }
            }
        }

        return null;
    }
}