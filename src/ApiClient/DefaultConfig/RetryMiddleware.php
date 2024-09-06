<?php

namespace Dagstuhl\SwhArchiveClient\ApiClient\DefaultConfig;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class RetryMiddleware
{
    public static function getDefault(): callable
    {
        return Middleware::retry(
            function (int $retries, RequestInterface $request, ?ResponseInterface $response, ?RuntimeException $e) {
                $maxRetries = 3;

                if ($retries >= $maxRetries) {
                    return false;
                }

                if ($e instanceof ConnectException) {
                    return true;
                }

                if ($response !== null && in_array($response->getStatusCode(), [ 249, 406, 429, 500, 502, 503, 504 ])) {
                    return true;
                }

                return false;
            }, function (int $retries) {
                return 1000 * $retries;
            }
        );
    }
}