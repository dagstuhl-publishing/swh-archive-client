<?php

namespace Dagstuhl\SwhArchiveClient\Repositories;

class RepositoryNode
{
    public readonly Repository $repository;
    public readonly string $url;
    public readonly string $path;
    public readonly string $pathPrefix;

    public function __construct(string $url)
    {
        $this->url = $url;
        $this->repository = Repository::fromNodeUrl($url);
        $this->path = $this->repository->getPath($url);

        $pathPrefix = str_replace($this->repository->getUrl(), '', $this->url);
        $pathPrefix = str_replace($this->path, '', $pathPrefix);
        $pathPrefix = preg_replace('#(/|\.git)$#', '', $pathPrefix);
        $pathPrefix = preg_replace('#^/-/|^/|/$#', '', $pathPrefix);
        $this->pathPrefix = $pathPrefix;
    }

    public function getPathSegments(): array
    {
        $segments = explode('/', $this->path);

        if (count($segments) === 1 && $segments[0] === '') {
            return [];
        }

        return $segments;
    }
}