<?php

namespace Dagstuhl\SwhArchiveClient\SwhObjects;

use Dagstuhl\SwhArchiveClient\ApiClient\Internal\FillFromApiData;
use Dagstuhl\SwhArchiveClient\ApiClient\Internal\SwhWebApiResource;
use Dagstuhl\SwhArchiveClient\Repositories\Repository;
use Dagstuhl\SwhArchiveClient\Repositories\RepositoryNode;

class Origin extends SwhWebApiResource
{
    use FillFromApiData;

    const ENDPOINT_ORIGIN_GET = 'origin/{origin_url}/get/';
    const ENDPOINT_ORIGIN_VISITS = 'origin/{origin_url}/visits/';
    const ENDPOINT_ORIGIN_VISIT = 'origin/{origin_url}/visit/{visit_number}/';
    const ENDPOINT_ORIGIN_SAVE_REQUESTS = 'origin/save/{visit_type}/url/{origin_url}/';
    const ID_PREFIX = 'swh:1:ori:';

    public readonly string $url;
    public readonly array $visitTypes;
    public readonly ?string $metadataAuthoritiesUrl;

    protected static array $apiPropRenaming = [
        'hasVisits' => 'has_visits'
    ];

    protected ?string $visitTypeFromUrl = null;

    public function __construct(array $apiData)
    {
        $this->fillFromApiData($apiData);
    }

    public static function fromRepository(Repository $repository, bool $fetchFromSwh = false): ?self
    {
        if ($fetchFromSwh) {
            $url = str_replace('{origin_url}', $repository->getUrl(), self::ENDPOINT_ORIGIN_GET);
            $response = self::getApiClient()->getResponse('GET', $url);

            if ($response !== null && $response->successful()) {
                $apiData = $response->json();
                return new self($apiData);
            }

            return null;
        }

        // instantiate from repository data (e.g. if origin is not yet present at Swh)
        return new static([
            'url' => $repository->getUrl(),
            'visit_types' => [ $repository->getType() ]
        ]);
    }

    public function exists(): ?bool
    {
        $url = str_replace('{origin_url}', $this->url, self::ENDPOINT_ORIGIN_GET);
        $response = self::getApiClient()->getResponse('GET', $url);

        if ($response === null) {
            return null;
        }

        return $response->successful();
    }

    public function getVisitTypeFromUrl(): string
    {
        if ($this->visitTypeFromUrl === null) {
            $this->visitTypeFromUrl = Repository::fromNodeUrl($this->url)->getType();
        }

        return $this->visitTypeFromUrl;
    }

    /**
     * @return Visit[]|null
     */
    public function getVisits(): ?array
    {
        $url = str_replace ('{origin_url}', $this->url, self::ENDPOINT_ORIGIN_VISITS);

        $response = self::getApiClient()->getResponse('GET', $url);

        if ($response !== null && $response->successful()) {
            $visits = [];
            foreach($response->json() as $apiData) {
                $visits[] = new Visit($apiData);
            }

            return $visits;
        }

        return null;
    }

    /**
     * @return SaveRequest[]|null
     */
    public function getSaveRequests(): ?array
    {
        $url = str_replace ('{origin_url}', $this->url, self::ENDPOINT_ORIGIN_SAVE_REQUESTS);
        $url = str_replace('{visit_type}', $this->getVisitTypeFromUrl(), $url);

        $response = self::getApiClient()->getResponse('GET', $url);

        if ($response !== null && $response->successful()) {
            $saveRequests = [];
            foreach($response->json() as $apiData) {
                $saveRequests[] = new SaveRequest($apiData);
            }
            return $saveRequests;
        }

        return NULL;
    }

    /**
     * auto-detect $visitType if not given
     */
    public function postSaveRequest(string $visitType = null): ?SaveRequest
    {
        $visitType = $visitType ?? $this->getVisitTypeFromUrl();

        $url = str_replace ('{origin_url}', $this->url, self::ENDPOINT_ORIGIN_SAVE_REQUESTS);
        $url = str_replace('{visit_type}', $visitType, $url);

        $response = self::getApiClient()->getResponse('POST', $url);

        if ($response !== null && $response->successful()) {
            return new SaveRequest($response->json());
        }

        return null;
    }

    // TODO: is this possible?
    public  function getVisitById(string $visitId): ?Visit
    {
        $url = str_replace(['{origin_url}', '{visit_number}'], [$this->url, $visitId], self::ENDPOINT_ORIGIN_VISIT);
        $response = self::getApiClient()->getResponse('GET', $url);

        if ($response !== null && $response->successful()) {
            $apiData = $response->json();
            return new Visit($apiData);
        }

        return null;
    }

    public function getIdentifier(): ?string
    {
        $authoritiesUrl = $this->metadataAuthoritiesUrl;

        if ($authoritiesUrl === null) {
            $authoritiesUrl = static::fromRepository((new RepositoryNode($this->url))->repository, true)?->metadataAuthoritiesUrl;
        }

        preg_match('#/('.static::ID_PREFIX.'[a-z0-9]*)/#', $authoritiesUrl, $matches);

        return $matches[1] ?? null;
    }
}