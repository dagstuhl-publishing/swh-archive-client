<?php

namespace Dagstuhl\SwhArchiveClient\ApiClient\Internal;

use Dagstuhl\SwhArchiveClient\ApiClient\SwhWebApiClient;

class SwhWebApiResource
{
    use FillFromApiData;

    private static ?SwhWebApiClient $apiClient = null;

    public static function getApiClient(): SwhWebApiClient
    {
        if (self::$apiClient === null) {
            self::$apiClient = SwhWebApiClient::getCurrent();
        }

        return self::$apiClient;
    }

    public static function setApiClient(SwhWebApiClient $swhApiClient): void
    {
        self::$apiClient = $swhApiClient;
    }
}