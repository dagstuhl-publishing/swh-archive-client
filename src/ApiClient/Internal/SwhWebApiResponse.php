<?php

namespace Dagstuhl\SwhArchiveClient\ApiClient\Internal;

use GuzzleHttp\Psr7\Response;

class SwhWebApiResponse
{
    public readonly Response $response;
    public readonly int $status;
    protected ?array $json = null;

    public function __construct(Response $response)
    {
        $this->response = $response;
        $this->status = $response->getStatusCode();
    }

    public function successful(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    public function serverError(): bool
    {
        return $this->status >= 500;
    }

    public function json(): array
    {
        if ($this->json === null) {
            $this->json = json_decode($this->response->getBody(), true);
        }

        return $this->json;
    }
}