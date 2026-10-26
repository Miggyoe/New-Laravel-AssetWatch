<?php

namespace App\Models;

use App\Enums\ReportPriority;
use App\Enums\WorkOrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class WorkOrder extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status'       => WorkOrderStatus::class,
            'priority'     => ReportPriority::class,
            'target_at'    => 'datetime',
            'accepted_at'  => 'datetime',
            'completed_at' => 'datetime',
            'reviewed_at'  => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // WO-2026-014 style reference
        static::creating(function (WorkOrder $order) {
            $order->reference_no ??= sprintf(
                'WO-%d-%03d',
                now()->year,
                static::whereYear('created_at', now()->year)->count() + 1
            );
        });
    }

    public function report()     { return $this->belongsTo(Report::class); }
    public function technician() { return $this->belongsTo(Technician::class); }
    public function creator()    { return $this->belongsTo(User::class, 'created_by'); }
    public function reviewer()   { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function updates()    { return $this->hasMany(WorkOrderUpdate::class)->orderBy('created_at')->orderBy('id'); }
    public function notes()      { return $this->hasMany(WorkOrderNote::class)->latest()->orderByDesc('id'); }

    /** $period: 'YYYY-MM', 'all' or null (no restriction). */
    public function scopeInPeriod(Builder $query, ?string $period): Builder
    {
        if (! $period || $period === 'all') {
            return $query;
        }

        $start = \Carbon\Carbon::createFromFormat('Y-m-d', $period . '-01')->startOfMonth();

        return $query->whereBetween('created_at', [$start, $start->copy()->endOfMonth()]);
    }

    /** Keys: q, status, technician_id, priority, period. */
    public function scopeFilter(Builder $query, array $f): Builder
    {
        return $query
            ->inPeriod($f['period'] ?? null)
            ->when($f['q'] ?? null, function ($q, $term) {
                $like = '%' . addcslashes($term, '%_\\') . '%';

                $q->where(fn ($w) => $w
                    ->where('reference_no', 'like', $like)
                    ->orWhere('title', 'like', $like)
                    ->orWhereHas('report', fn ($r) => $r->where('reference_no', 'like', $like)));
            })
            ->when($f['status'] ?? null,        fn ($q, $v) => $q->where('status', $v))
            ->when($f['technician_id'] ?? null, fn ($q, $v) => $q->where('technician_id', $v))
            ->when($f['priority'] ?? null,      fn ($q, $v) => $q->where('priority', $v));
    }
}
