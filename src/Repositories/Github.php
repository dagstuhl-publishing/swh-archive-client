<?php

namespace Dagstuhl\SwhArchiveClient\Repositories;

final class Github extends Repository
{
    const GITHUB_HOST_PATTERN = '/github\.com/i';

    protected string $type = self::TYPE_GIT;

    protected const BREAKPOINTS = [
        '/tree/',
        '/blob/',
        '/pull/',
        '/commit/',
        '/releases/tag/',
        '/releases/',
        '/pkgs/container/',
        '/packages/container/'
    ];

    public static function isAppropriateClassFor(string $url): bool
    {
        $url = new Url($url);
        return preg_match(self::GITHUB_HOST_PATTERN, $url->getHostName()) === 1;
    }

    protected static function getBaseUrl(string $url): string
    {
        $url = new Url($url);
        $pathArray = $url->getPathArray();

        $baseUrl = $url->getHostUrl(). '/'. $pathArray[0] . '/' . $pathArray[1];
        $baseUrl = preg_replace('#\.git$#', '', $baseUrl);

        return preg_replace('#/$#', '', $baseUrl);
    }
}