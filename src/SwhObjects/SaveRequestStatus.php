<?php

namespace Dagstuhl\SwhArchiveClient\SwhObjects;

enum SaveRequestStatus: string
{
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case PENDING = 'pending';
}