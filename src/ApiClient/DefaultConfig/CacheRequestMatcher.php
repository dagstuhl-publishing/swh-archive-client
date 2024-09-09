<?php

namespace Dagstuhl\SwhArchiveClient\ApiClient\DefaultConfig;

use Kevinrob\GuzzleCache\Strategy\Delegate\RequestMatcherInterface;
use Psr\Http\Message\RequestInterface;

class CacheRequestMatcher implements RequestMatcherInterface
{
    public function matches(RequestInterface $request): bool
    {
        $uri = $request->getUri();
        return !str_contains($uri, '/origin/') && !str_contains($uri, '/stat/counters');
    }
}