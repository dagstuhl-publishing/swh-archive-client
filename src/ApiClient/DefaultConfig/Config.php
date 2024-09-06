<?php

namespace Dagstuhl\SwhArchiveClient\ApiClient\DefaultConfig;

use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\CacheMiddleware;

class Config
{
    public static function getDefaultOptions(): array
    {
        $handlerStack = HandlerStack::create();
        $handlerStack->push(RetryMiddleware::getDefault(), 'retry');
        $handlerStack->push(new CacheMiddleware(CacheStrategy::getDefault()), 'cache');

        return [
            'debug' => false,
            'allow_redirects' => [ 'max' => 1, 'protocols' => [ 'https' ], 'track_redirects' => true ],
            'force_ip_resolve' => 'v4',
            'http_errors' => false,   // throws them to RequestException rather than GuzzleRequestException
            'verify' => true,
            'connect_timeout' => 12,
            'timeout' => 15,
            'handler' => $handlerStack,
        ];
    }
}