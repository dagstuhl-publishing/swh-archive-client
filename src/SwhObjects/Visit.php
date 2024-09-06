<?php

namespace Dagstuhl\SwhArchiveClient\SwhObjects;

use Dagstuhl\SwhArchiveClient\ApiClient\Internal\FillFromApiData;
use Dagstuhl\SwhArchiveClient\ApiClient\Internal\SwhWebApiResource;
use Carbon\Carbon;

class Visit extends SwhWebApiResource
{
    use FillFromApiData;

    public readonly string $originUrl;
    public readonly int $visit;
    public readonly Carbon $date;
    public readonly string $status;
    public readonly string $snapshot;
    public readonly string $type;

    protected static array $apiPropRenaming = [
        'originUrl' => 'origin'
    ];

    public function __construct(array $apiData)
    {
        $this->fillFromApiData($apiData);
    }

    public function getSnapshot(): Snapshot
    {
        return Snapshot::byId($this->snapshot);
    }
}