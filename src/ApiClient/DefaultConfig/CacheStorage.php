<?php

namespace Dagstuhl\SwhArchiveClient\ApiClient\DefaultConfig;

use Kevinrob\GuzzleCache\Storage\CacheStorageInterface;
use Kevinrob\GuzzleCache\Storage\FlysystemStorage;
use League\Flysystem\Local\LocalFilesystemAdapter;

class CacheStorage
{
    public static function getDefault(string $cacheDirectory = null): CacheStorageInterface
    {
        $cacheDirectory = $cacheDirectory ?? config('swh.web-api.cache-folder');

        return new FlysystemStorage(
            new LocalFilesystemAdapter($cacheDirectory)
        );
    }
}