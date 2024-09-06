<?php

namespace Dagstuhl\SwhArchiveClient\SwhObjects;

use Dagstuhl\SwhArchiveClient\ApiClient\Internal\FillFromApiData;

class Content
{
    use FillFromApiData;

    public readonly array $content;

    public readonly string $path;

    public readonly string $revision;

    public readonly string $type;

    public function __construct(array $apiData)
    {
        $this->fillFromApiData($apiData);
    }

    public function getSha1GitChecksum(): ?string
    {
        return $this->content['checksums']['sha1_git'] ?? null;
    }

    public function getRevision(): ?Revision
    {
        return Revision::byId($this->revision);
    }
}