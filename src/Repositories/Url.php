<?php

namespace Dagstuhl\SwhArchiveClient\Repositories;

class Url
{
    protected string $url;
    protected array $decomposedUrl;

    public function __construct(string $url)
    {
        $url = preg_replace('/\/$/i','', rawurldecode($url));
        $this->url = $url;
        $this->decomposedUrl = parse_url($url);
    }

    public function toString(): string
    {
        return $this->url;
    }

    public function getHostName(): string
    {
        return $this->decomposedUrl['host'];
    }

    public function getPath(): string
    {
        return $this->decomposedUrl['path'] ?? '';
    }

    public function getPathArray(): array
    {
        $path = preg_replace('#^/#', '', $this->getPath());
        return explode('/', $path);
    }

    public function getHostUrl(): string
    {
        return $this->decomposedUrl['scheme'] . '://' . $this->decomposedUrl['host'];
    }
}