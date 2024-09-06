<?php

namespace Dagstuhl\SwhArchiveClient\SwhObjects;

class Context
{
    public const SWH_ID_TEMPLATES = [
        'Directory-Context' => '{ DIR_ID };origin={ ORIGIN_URL };visit={ SNP_ID };anchor={ REV_ID };path={ PATH }',
        'Content-Context' =>   '{ CNT_ID };origin={ ORIGIN_URL };visit={ SNP_ID };anchor={ REV_ID };path={ PATH }',
    ];

    public readonly Origin $origin;
    public readonly Snapshot $snapshot;
    public readonly Revision $revision;
    public readonly Directory $directory;
    public readonly ?Content $content;
    public readonly ?string $path;

    public function __construct(
        Origin $origin,
        Snapshot $snapshot,
        Revision $revision,
        Directory $directory = null,
        Content $content = null,
        string $path = null
    )
    {
        $this->origin = $origin;
        $this->snapshot = $snapshot;
        $this->revision = $revision;
        $this->directory = $directory ?? $revision->getDirectory();
        $this->content = $content;

        $path = trim($path ?? '');
        $path = preg_replace('#^/?commits/[a-zA-Z0-9]*(/|$)#', '/', $path);

        if ($path === '' || $path === '/') {
            $path = null;
        }
        else {
            if (!str_ends_with($path, '/') && $content === null) {
                $path = $path . '/';
            }
            if (!str_starts_with($path, '/')) {
                $path = '/' . $path;
            }
        }

        $this->path = $path;
    }

    public function getIdentifier(): string
    {
        $checksum = $this->content?->getSha1GitChecksum();

        if ($checksum !== null) {
            $id = static::SWH_ID_TEMPLATES['Content-Context'];
            $id = str_replace('{ CNT_ID }', 'swh:1:cnt:'. $checksum, $id);
        }
        else {
            $id = static::SWH_ID_TEMPLATES['Directory-Context'];
            $id = str_replace('{ DIR_ID }', $this->directory->getIdentifier(), $id);
        }

        if ($this->path !== null) {
            $id = str_replace('{ PATH }', $this->path, $id);
        }

        $id = str_replace('{ ORIGIN_URL }', $this->origin->url, $id);
        $id = str_replace('{ SNP_ID }', $this->snapshot->getIdentifier(), $id);
        $id = str_replace('{ REV_ID }', $this->revision->getIdentifier(), $id);

        // remove unused PATH context
        return str_replace(';path={ PATH }', '', $id);
    }

    public function getIdentifierArray(): array
    {
        $context = [
            'ori' => $this->origin->getIdentifier(),
            'snp' => $this->snapshot->getIdentifier(),
            'rev' => $this->revision->getIdentifier()
        ];

        $checksum = $this->content?->getSha1GitChecksum();

        if ($checksum !== null) {
            $context['cnt'] = 'swh:1:cnt:'. $checksum;
        }
        else {
            $context['dir'] = $this->directory->getIdentifier();
        }

        return $context;
    }
}