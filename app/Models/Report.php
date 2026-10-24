<?php

namespace App\Models;

use App\Enums\ReportPriority;
use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status'      => ReportStatus::class,
            'priority'    => ReportPriority::class,
            'resolved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // AW-2026-001 style reference. Fine for a single-campus admin app; switch to a
        // dedicated sequence table if reports are ever created concurrently at volume.
        static::creating(function (Report $report) {
            $report->reference_no ??= sprintf(
                'AW-%d-%03d',
                now()->year,
                static::withTrashed()->whereYear('created_at', now()->year)->count() + 1
            );
        });
    }

    public function reporter()    { return $this->belongsTo(User::class, 'reporter_id'); }
    public function assignee()    { return $this->belongsTo(User::class, 'assigned_to'); }
    public function asset()       { return $this->belongsTo(Asset::class); }
    public function category()    { return $this->belongsTo(Category::class); }
    public function location()    { return $this->belongsTo(Location::class); }
    public function attachments() { return $this->hasMany(ReportAttachment::class); }
    public function updates()     { return $this->hasMany(ReportUpdate::class); }

    /** "Academic Bldg · 203" */
    public function placeLabel(): string
    {
        if (! $this->location) {
            return '—';
        }

        return trim(($this->location->building?->name ?? '') . ' · ' . $this->location->name, ' ·');
    }
}
