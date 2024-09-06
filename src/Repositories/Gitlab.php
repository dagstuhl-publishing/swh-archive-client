<?php

namespace Dagstuhl\SwhArchiveClient\Repositories;

use GuzzleHttp\Client;
use Throwable;

final class Gitlab extends Repository
{
    const GITLAB_HOST_PATTERN = '/gitlab/i';

    protected string $type = self::TYPE_GIT;

    protected const BREAKPOINTS = [
        '/tree/',
        '/blob/',
        '/pull/',
        '/commit/',
        '/releases/',
        '/pkgs/container/',
        '/packages/container/'
    ];

    public static function isAppropriateClassFor(string $url): bool
    {
        return (
            preg_match(self::GITLAB_HOST_PATTERN, (new Url($url))->getHostName())
            || self::hasGitlabHeader($url)
        );
    }

    protected static function hasGitlabHeader(string $url): bool
    {
        $url = new Url($url);

        $client = new Client([ 'allow_redirects' => [ 'max' => 1 ] ]);
        try {
            $helpUrl = $url->getHostUrl().'/help';
            $response = $client->request('HEAD', $helpUrl);

            return (
                array_key_exists('X-Gitlab-Meta', $response->getHeaders())
                || str_contains($response->getHeaderLine('Set-Cookie'), '_gitlab_session')
                || str_contains(@file_get_contents($helpUrl), 'GitLab')
            );

        }
        catch (Throwable $e) {
            return false;
        }
    }

    protected static function getBaseUrl(string $url): string
    {
        $baseUrl = explode('/-/', $url)[0];
        $baseUrl = preg_replace('#\.git$#', '', $baseUrl);

        return preg_replace('#/$#', '', $baseUrl);
    }

    public function getPath(string $url): string
    {
        $baseUrl = $this->getUrl();
        $url = preg_replace('#/$#', '', $url);

        $path = str_replace($baseUrl, '', $url);
        $path = trim($path);
        $path = preg_replace('#^/-/#', '/', $path);

        return Repository::getPath($path);
    }

}