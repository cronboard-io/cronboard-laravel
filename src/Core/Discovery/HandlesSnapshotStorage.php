<?php

namespace Cronboard\Core\Discovery;

use Cronboard\Core\Discovery\Snapshot;
use Cronboard\Support\Storage\Storage;

trait HandlesSnapshotStorage
{
    protected function storeSnapshot(Snapshot $snapshot)
    {
        $this->getStorage()->store($this->getSnapshotKey(), $snapshot->toArray());
    }

    protected function getStorage()
    {
        return $this->app->make(Storage::class);
    }

    protected function getSnapshotKey(): string
    {
        return 'cronboard.snapshot';
    }
}
