<?php

namespace Dagstuhl\SwhArchiveClient\SwhObjects;

use Dagstuhl\SwhArchiveClient\ApiClient\Internal\FillFromApiData;
use Dagstuhl\SwhArchiveClient\ApiClient\Internal\SwhWebApiResource;
use Dagstuhl\SwhArchiveClient\Repositories\RepositoryNode;

class Snapshot extends SwhWebApiResource
{
    use FillFromApiData;

    const ENDPOINT_SNAPSHOT = 'snapshot/{snapshot_id}/';
    const ENDPOINT_SNAPSHOT_QUERY = '?branches_from={next_branch}';
    const ID_PREFIX = 'swh:1:snp:';

    public readonly string $id;
    public readonly array $branches;
    public readonly ?string $nextBranch;

    protected ?array $cachedBranches = null;

    protected static array $apiPropRenaming = [
        'nextBranch' => 'next_branch'
    ];

    public function __construct(array $apiData)
    {
        $this->fillFromApiData($apiData);
    }

    public static function byId(string $snapshotId, string $nextBranch = null): ?static
    {
        $snapshotId = str_replace(static::ID_PREFIX, '', $snapshotId);

        $urlQuery = null;
        if ($nextBranch !== null) {
            $urlQuery= str_replace('{next_branch}', $nextBranch, self::ENDPOINT_SNAPSHOT_QUERY);
        }

        $url = str_replace('{snapshot_id}', $snapshotId, self::ENDPOINT_SNAPSHOT.$urlQuery??'');
        $response = self::getApiClient()->getResponse('GET', $url);

        if ($response !== null && $response->successful()) {
            $apiData = $response->json();
            return new static($apiData);
        }

        return null;
    }

    /**
     * @return array<Branch>
     */
    public function getBranches(): array
    {
        if (is_array($this->cachedBranches)) {
            return $this->cachedBranches;
        }

        $branches = [];

        foreach ($this->branches as $branchName => $branchApiData) {
            $branches[]= new Branch($branchName, $branchApiData);
        }

        if ($this->nextBranch !== null) {
            $paginatedSnapshot = self::byId($this->id, $this->nextBranch);

            $branches = array_merge($branches, $paginatedSnapshot->getBranches());
        }

        $this->cachedBranches = $branches;

        return $branches;
    }

    public function getDefaultBranch(): ?Branch
    {
        $branches = $this->getBranches();

        $target = null;
        foreach($branches as $branch) {
            if ($branch->name === 'HEAD') {
                $target = $branch->target;
                break;
            }
        }

        if ($target === null) {
            return null;
        }

        foreach($branches as $branch) {
            if ($branch->name === $target) {
                return $branch;
            }
        }

        return null;
    }

    public function getIdentifier(): string
    {
        return static::ID_PREFIX.$this->id;
    }

    public function getContext(RepositoryNode $repoNode): ?Context
    {
        $repo = $repoNode->repository;
        $origin = Origin::fromRepository($repo);

        $branches = $this->getBranches();
        $handledPathSegments = [];
        $unhandledPathSegments = $repoNode->getPathSegments();

        $shortRevisionHash = null;
        $branchMatch = null;
        $revision = null;

        // 1st step: handle /blob/{long-commit-hash}/ case
        preg_match('#/blob/([0-9a-z]{40})(/|$)#', $repoNode->url, $matches);
        $commitHash = $matches[1] ?? null;

        if ($commitHash !== null) {
            $revision = Revision::byId($commitHash);
            $parts = explode('/blob/'.$commitHash.'/', $repoNode->url);
            $unhandledPathSegments = explode('/', $parts[1] ?? '');
        }
        // 2nd step: try to identify branch otherwise
        else {
            while (count($unhandledPathSegments) > 0 && $branchMatch === null) {
                $handledPathSegments[] = $unhandledPathSegments[0];
                unset($unhandledPathSegments[0]);
                $unhandledPathSegments = array_values($unhandledPathSegments);

                $branchCandidate = implode('/', $handledPathSegments);

                $branchMatch = null;

                foreach ($branches as $branch) {
                    // pull request
                    if (is_numeric($branchCandidate) && $branch->name === 'refs/pull/' . $branchCandidate . '/head') {
                        $branchMatch = $branch;
                        break;
                    }

                    // long commit-hash
                    if ($branch->target === $branchCandidate) {
                        $branchMatch = $branch;
                        break;
                    }

                    // branch name
                    // TODO: more specific
                    // ($branch->name === 'refs/heads/'.$branchCandidate || $branch->name === 'refs/tags/'.$branchCandidate)
                    // further cases?
                    if (str_ends_with($branch->name, '/' . $branchCandidate)) {
                        $branchMatch = $branch;
                        break;
                    }
                }

                // short commit hash
                preg_match('/^[0-9a-f]{7}$/', $branchCandidate, $matches);
                if (count($matches) === 1 && count($handledPathSegments) === 1) {
                    $shortRevisionHash = $matches[0];
                    break;
                }
            }
        }

        // 3rd step: remaining case should be base repo

        if ($revision === null) {
            if ($branchMatch === null) {
                $branchMatch = $this->getDefaultBranch();
            }

            if ($branchMatch === null) {
                foreach ($branches as $branch) {
                    if ($branch->name === 'refs/heads/main' || $branch->name === 'refs/heads/master') {
                        $branchMatch = $branch;
                        break;
                    }
                }
            }

            $revision = $branchMatch?->getRevision();
        }

        if ($shortRevisionHash !== null) {
            $revision = $revision->getParentRevisionByHash($shortRevisionHash);
        }

        // 4th step: identify directory / content
        $directory = null;
        $contentMatch = null;
        $fullPath = implode('/', $unhandledPathSegments);

        if ($branchMatch !== null || $commitHash !== null) {
            $parentDirectory = $revision->getDirectory();

            while (count($unhandledPathSegments) > 0 && $contentMatch === null && $parentDirectory !== null) {
                $directoryCandidate = $unhandledPathSegments[0];
                unset($unhandledPathSegments[0]);
                $unhandledPathSegments = array_values($unhandledPathSegments);

                $parentDirectoryContents = $parentDirectory->getContents();

                $parentDirectory = null;
                $directoryCandidate = urldecode($directoryCandidate);   // %20 -> ' '
                foreach($parentDirectoryContents as $entry) {
                    if ($entry['type'] === 'dir' && $entry['name'] === $directoryCandidate) {
                        $parentDirectory = Directory::byId($entry['target']);
                        break;
                    }
                }

                if ($parentDirectory === null) {
                    $contentMatch = $revision->getContent($fullPath);
                }
            }

            if ($contentMatch === null) {
                $directory = $parentDirectory;
            }
        }

        if ($revision !== null) {
            return new Context($origin, $this, $revision, $directory, $contentMatch, $fullPath);
        }

        return null;
    }
}