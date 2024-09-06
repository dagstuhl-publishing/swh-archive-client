<?php

namespace Dagstuhl\SwhArchiveClient\ApiClient\DefaultConfig;

use Kevinrob\GuzzleCache\Strategy\CacheStrategyInterface;
use Kevinrob\GuzzleCache\Strategy\Delegate\DelegatingCacheStrategy;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;

class CacheStrategy
{
    public static function getDefault(): CacheStrategyInterface
    {
        $strategy = new DelegatingCacheStrategy();
        $strategy->registerRequestMatcher(
            new CacheRequestMatcher(),
            new GreedyCacheStrategy(CacheStorage::getDefault(), config('swh.web-api.cache-ttl') ?? 86400 * 365)
        );

        return $strategy;
    }
}