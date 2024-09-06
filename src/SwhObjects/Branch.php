<?php

namespace Dagstuhl\SwhArchiveClient\SwhObjects;

class Branch
{
    public readonly string $name;

    public readonly string $target;
    public readonly string $targetType;



    public function __construct(string $branchName, array $branchData)
    {
        $this->name = $branchName;
        $this->target = $branchData['target'];
        $this->targetType = $branchData['target_type'];
    }

    public function getTarget(): null|Revision|Release
    {
        return match($this->targetType) {
            'revision' => Revision::byId($this->target),
            'release' => Release::byId($this->target),
            default => null
        };
    }

    public function getRevision(): null|Revision
    {
        $target = $this->getTarget();

        if ($target instanceof Revision) {
            return $target;
        }

        if ($target instanceof Release) {
            return $target->getRevision();
        }

        return null;
    }

}