<?php

namespace App\Models;

use App\Enums\PersonnelAvailability;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Technician extends Model
{
    public const OPEN_STATUSES = ['assigned', 'accepted', 'in_progress'];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_available' => 'boolean'];
    }

    public function user()       { return $this->belongsTo(User::class); }
    public function workOrders() { return $this->hasMany(WorkOrder::class); }

    /** "Ramon Dela Cruz · Carpentry" */
    public function label(): string
    {
        return $this->name . ' · ' . $this->specialty;
    }

    public function initial(): string
    {
        return mb_strtoupper(mb_substr($this->name, 0, 1));
    }

    /** Busy when the open work orders reach the threshold; unavailable when switched off or the account is inactive. */
    public function availability(?int $openOrders = null): PersonnelAvailability
    {
        if (! $this->is_active || ! $this->is_available) {
            return PersonnelAvailability::Unavailable;
        }

        $open = $openOrders ?? $this->active_orders_count ?? $this->workOrders()->whereIn('status', self::OPEN_STATUSES)->count();

        return $open >= (int) config('mcc.busy_threshold', 7) ? PersonnelAvailability::Busy : PersonnelAvailability::Available;
    }

    public function scopeWithActiveOrders(Builder $query): Builder
    {
        return $query->withCount(['workOrders as active_orders_count' => fn ($q) => $q->whereIn('status', self::OPEN_STATUSES)]);
    }

    public function scopeWhereAvailability(Builder $query, ?string $availability): Builder
    {
        $open      = "(select count(*) from work_orders where work_orders.technician_id = technicians.id and work_orders.status in ('assigned','accepted','in_progress'))";
        $threshold = (int) config('mcc.busy_threshold', 7);

        return match ($availability) {
            'available'   => $query->where('is_active', true)->where('is_available', true)->whereRaw("$open < ?", [$threshold]),
            'busy'        => $query->where('is_active', true)->where('is_available', true)->whereRaw("$open >= ?", [$threshold]),
            'unavailable' => $query->where(fn ($w) => $w->where('is_active', false)->orWhere('is_available', false)),
            default       => $query,
        };
    }

    /** Keys: q, specialty, availability, account */
    public function scopeFilter(Builder $query, array $f): Builder
    {
        return $query
            ->when($f['q'] ?? null, function ($q, $term) {
                $like = '%' . addcslashes($term, '%_\\') . '%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('staff_id', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->when($f['specialty'] ?? null, fn ($q, $v) => $q->where('specialty', $v))
            ->when($f['availability'] ?? null, fn ($q, $v) => $q->whereAvailability($v))
            ->when(($f['account'] ?? null) === 'active', fn ($q) => $q->where('is_active', true))
            ->when(($f['account'] ?? null) === 'inactive', fn ($q) => $q->where('is_active', false));
    }
}
