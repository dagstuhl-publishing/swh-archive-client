<?php

namespace Dagstuhl\SwhArchiveClient\ApiClient\DefaultConfig;

use Kevinrob\GuzzleCache\Storage\CacheStorageInterface;
use Kevinrob\GuzzleCache\Storage\FlysystemStorage;
use League\Flysystem\Local\LocalFilesystemAdapter;

class CacheStorage
{
    public static function getDefault(string $cacheDirectory = null): CacheStorageInterface
    {
        $cacheDirectory = config('swh.web-api.cache-folder') ?? $cacheDirectory;

        return new FlysystemStorage(
            new LocalFilesystemAdapter($cacheDirectory)
        );
    }
}