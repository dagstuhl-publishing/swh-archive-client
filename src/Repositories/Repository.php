<?php

namespace Dagstuhl\SwhArchiveClient\Repositories;

class Repository
{
    public const TYPE_UNKNOWN = 'unknown';
    public const TYPE_GIT = 'git';

    public const SUPPORTED_REPOSITORIES = [
        Bitbucket::class,
        Github::class,
        Gitlab::class
    ];

    protected const BREAKPOINTS = [];

    protected string $url;
    protected string $type = self::TYPE_UNKNOWN;

    protected function __construct(string $url)
    {
        $this->url = $url;
    }

    protected static function getBaseUrl(string $url): string
    {
        return $url;    // to be overridden by child class
    }

    // TODO: check if at least one two slashes are present: one following the host, one following the username
    public static function fromNodeUrl(string $url): static
    {
        /** @var Github|Gitlab|Bitbucket $repositoryClass */
        foreach (self::SUPPORTED_REPOSITORIES as $repositoryClass) {
            if ($repositoryClass::isAppropriateClassFor($url)) {
                $baseUrl = $repositoryClass::getBaseUrl($url);
                return new $repositoryClass($baseUrl);
            }
        }

        return new self($url);
    }


    public function getType(): string
    {
        return $this->type;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getPath(string $url): string
    {
        $baseUrl = $this->getUrl();
        $url = preg_replace('#/$#', '', $url);
        $url = preg_replace('#\.git$#', '', $url);

        $path = str_replace($baseUrl, '', $url);
        $path = trim($path);

        if (count(static::BREAKPOINTS) > 0) {
            $regex = implode('|', static::BREAKPOINTS);
            $regex = '#' . $regex . '#';

            $path = preg_replace($regex, '', $path);
        }

        return preg_replace('#^/#', '', $path);
    }
}