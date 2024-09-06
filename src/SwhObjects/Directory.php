<?php

namespace Dagstuhl\SwhArchiveClient\SwhObjects;

use Dagstuhl\SwhArchiveClient\ApiClient\Internal\SwhWebApiResource;

class Directory extends SwhWebApiResource
{

    const ENDPOINT_DIRECTORY = 'directory/{directory_id}/';
    const ID_PREFIX = 'swh:1:dir:';

    public readonly string $directory;

    // todo: there is no top-level metadata in the api response but list of directory contents. Usual construct() won't work
    // https://archive.softwareheritage.org/api/1/directory/977fc4b98c0e85816348cebd3b12026407c368b6/

    public function __construct(string $directoryId)
    {
        $this->directory = $directoryId;
    }

    public static function byId(string $directoryId): static
    {
        $directoryId = str_replace(static::ID_PREFIX, '', $directoryId);
        return new static($directoryId);
    }

    public function getContents(): ?array
    {
        $url = str_replace('{directory_id}', $this->directory, self::ENDPOINT_DIRECTORY);
        $response = self::getApiClient()->getResponse('GET', $url);

        if ($response !== null && $response->successful()) {
            $apiData = $response->json();
            return $apiData;
        }

        return null;
    }

    public function getIdentifier(): string
    {
        return static::ID_PREFIX . $this->directory;
    }

}