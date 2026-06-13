<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
{
    protected $fillable = [
        'type',
        'status',
        'matches_fetched',
        'matches_updated',
        'message',
        'meta',
        'duration_seconds',
    ];

    protected $casts = [
        'meta' => 'array',
        'matches_fetched' => 'integer',
        'matches_updated' => 'integer',
        'duration_seconds' => 'float',
    ];

    public function markSuccess(int $fetched, int $updated, float $duration, ?array $meta = null): void
    {
        $this->update([
            'status' => 'success',
            'matches_fetched' => $fetched,
            'matches_updated' => $updated,
            'duration_seconds' => $duration,
            'meta' => $meta,
        ]);
    }

    public function markFailed(string $message, ?array $meta = null): void
    {
        $this->update([
            'status' => 'failed',
            'message' => $message,
            'meta' => $meta,
        ]);
    }
}
