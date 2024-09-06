<?php

namespace Dagstuhl\SwhArchiveClient\Repositories;

class Bitbucket extends Repository
{
    private const BITBUCKET_HOST_PATTERN = '/bitbucket\.org/i';
    protected const BREAKPOINTS = ['/src/'];

    protected string $type = self::TYPE_GIT;

    public static function isAppropriateClassFor(string $url): bool
    {
        $url = new Url($url);
        return preg_match(self::BITBUCKET_HOST_PATTERN, $url->getHostName()) === 1;
    }

    protected static function getBaseUrl(string $url): string
    {
        $url = new Url($url);
        $pathArray = $url->getPathArray();

        return $url->getHostUrl(). '/'. $pathArray[0] . '/' . $pathArray[1];
    }

}