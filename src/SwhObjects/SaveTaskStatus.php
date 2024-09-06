<?php

namespace Dagstuhl\SwhArchiveClient\SwhObjects;

enum SaveTaskStatus: string
{
    case NOT_CREATED = 'not created';
    case PENDING = 'pending';
    case SCHEDULED = 'scheduled';
    case RUNNING = 'running';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';
}