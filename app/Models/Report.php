<?php

namespace App\Models;

use App\Enums\ReportPriority;
use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Builder;
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

    /** "Academic · 203" (compact, as used in the report tables) */
    public function locationShort(): string
    {
        if (! $this->location) {
            return '—';
        }

        return trim(($this->location->building?->short_name ?? '') . ' · ' . $this->location->name, ' ·');
    }

    /** Start date for a period preset (null = no date restriction). */
    public static function periodStart(?string $period): ?\Carbon\CarbonInterface
    {
        return match ($period) {
            '7d'    => now()->subDays(6)->startOfDay(),
            '30d'   => now()->subDays(29)->startOfDay(),
            'month' => now()->startOfMonth(),
            'year'  => now()->startOfYear(),
            default => null,
        };
    }

    /**
     * Shared by the report list and the CSV export.
     * Keys: q, status, priority, category_id, building_id, period.
     */
    public function scopeFilter(Builder $query, array $f): Builder
    {
        return $query
            ->when($f['q'] ?? null, function ($q, $term) {
                $like = '%' . addcslashes($term, '%_\\') . '%';

                $q->where(fn ($w) => $w->where('reference_no', 'like', $like)->orWhere('title', 'like', $like));
            })
            ->when($f['status'] ?? null,      fn ($q, $v) => $q->where('status', $v))
            ->when($f['priority'] ?? null,    fn ($q, $v) => $q->where('priority', $v))
            ->when($f['category_id'] ?? null, fn ($q, $v) => $q->where('category_id', $v))
            ->when($f['building_id'] ?? null, fn ($q, $id) => $q->whereHas('location', fn ($l) => $l->where('building_id', $id)))
            ->when(static::periodStart($f['period'] ?? null), fn ($q, $from) => $q->where('created_at', '>=', $from));
    }
}
