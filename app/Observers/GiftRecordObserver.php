<?php

namespace App\Observers;

use App\Models\GiftRecord;
use RuntimeException;

class GiftRecordObserver
{
    /**
     * Prevent updates to gift records (append-only ledger).
     */
    public function updating(GiftRecord $giftRecord): bool
    {
        throw new RuntimeException('Gift records are immutable and append-only. Updates are strictly forbidden.');
    }

    /**
     * Prevent deletions of gift records (append-only ledger).
     */
    public function deleting(GiftRecord $giftRecord): bool
    {
        throw new RuntimeException('Gift records are immutable and append-only. Deletions are strictly forbidden.');
    }
}
