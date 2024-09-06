<?php

namespace Dagstuhl\SwhArchiveClient\ApiClient\DefaultConfig;

use Kevinrob\GuzzleCache\Strategy\Delegate\RequestMatcherInterface;
use Psr\Http\Message\RequestInterface;

class CacheRequestMatcher implements RequestMatcherInterface
{
    public function matches(RequestInterface $request): bool
    {
        return !str_contains($request->getUri(), '/origin/');
    }
}