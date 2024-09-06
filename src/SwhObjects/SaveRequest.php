<?php

namespace Dagstuhl\SwhArchiveClient\SwhObjects;

use Dagstuhl\SwhArchiveClient\ApiClient\Internal\FillFromApiData;
use Dagstuhl\SwhArchiveClient\ApiClient\Internal\SwhWebApiResource;
use Carbon\Carbon;

class SaveRequest extends SwhWebApiResource
{
    use FillFromApiData;

    const ENDPOINT_SAVE_REQUEST_GET = 'origin/save/{save_request_id}/';


    public readonly int $id;
    public readonly string $originUrl;
    public readonly Carbon $saveRequestDate;
    public readonly SaveRequestStatus $saveRequestStatus;
    public readonly SaveTaskStatus $saveTaskStatus;
    public readonly ?string $visitStatus;
    public readonly ?string $visitType;
    public readonly ?Carbon $visitDate;
    public readonly int $loadingTaskId;
    public readonly ?string $snapshotSwhId;

    protected static array $apiPropRenaming = [
        'snapshotSwhId' => 'snapshot_swhid'
    ];

    public function __construct(array $apiData)
    {
        $this->fillFromApiData($apiData);
    }

    public static function byId(string $saveRequestId): ?static
    {
        $url = str_replace('{save_request_id}', $saveRequestId, self::ENDPOINT_SAVE_REQUEST_GET);
        $response = self::getApiClient()->getResponse('GET', $url);

        if ($response !== null && $response->successful()) {
            $apiData = $response->json();
            return new self($apiData);
        }

        return null;
    }

    public function getSnapshot(): ?Snapshot
    {
        if ($this->snapshotSwhId === null) {
            return null;
        }

        $id = str_replace(Snapshot::ID_PREFIX, '', $this->snapshotSwhId);
        return Snapshot::byId($id);
    }

    public function fetch(): ?static
    {
        return static::byId($this->id);
    }
}