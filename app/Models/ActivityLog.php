<?php

namespace App\Models;

use App\Enums\ReportStatus;
use App\Enums\WorkOrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ActivityLog extends Model
{
    /** Wording of the "Action performed" column. */
    public const LABELS = [
        'report.created'          => 'Report submitted',
        'report.verified'         => 'Report verified',
        'report.rejected'         => 'Report rejected',
        'report.resolved'         => 'Report resolved',
        'report.corrected'        => 'Report corrected',
        'report.priority_changed' => 'Priority changed',
        'report.note_added'       => 'Report note added',
        'work_order.created'      => 'Work order created',
        'work_order.accepted'     => 'Work order accepted',
        'work_order.started'      => 'Repair started',
        'work_order.completed'    => 'Repair completed',
        'work_order.cancelled'    => 'Work order cancelled',
        'work_order.reassigned'   => 'Technician reassigned',
        'work_order.note_added'   => 'Work order note added',
    ];

    public const MODULES = [
        'report'     => 'Incident reports',
        'work_order' => 'Work orders',
        'personnel'  => 'Personnel',
        'user'       => 'Users',
        'location'   => 'Locations',
        'category'   => 'Categories',
        'auth'       => 'Sign-ins',
    ];

    protected $guarded = [];

    public function user()    { return $this->belongsTo(User::class); }
    public function subject() { return $this->morphTo(); }

    /** Writes one audit entry for the signed-in user. $from / $to are the status before and after, when there is one. */
    public static function record(string $action, string $description, ?Model $subject = null, ?string $from = null, ?string $to = null): self
    {
        return static::create([
            'user_id'      => auth()->id(),
            'action'       => $action,
            'description'  => $description,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id'   => $subject?->getKey(),
            'from_status'  => $from,
            'to_status'    => $to,
            'ip_address'   => request()->ip(),
        ]);
    }

    public function label(): string
    {
        if ($this->action === 'report.status_changed') {
            return $this->to_status === 'resolved' ? 'Report resolved' : 'Report status changed';
        }

        return self::LABELS[$this->action] ?? ($this->moduleLabel() . ' ' . Str::lower($this->verbLabel()));
    }

    public static function labelFor(string $action): string
    {
        return $action === 'report.status_changed' ? 'Report status changed' : (self::LABELS[$action] ?? Str::headline(str_replace(['.', '_'], ' ', $action)));
    }

    public function moduleLabel(): string
    {
        $module = Str::before($this->action, '.');

        return self::MODULES[$module] ?? Str::headline($module);
    }

    public function verbLabel(): string
    {
        return Str::of(Str::after($this->action, '.'))->replace('_', ' ')->ucfirst()->toString();
    }

    public function roleLabel(): string
    {
        return match ($this->user?->role) {
            null          => '—',
            'admin'       => 'Administrator',
            'technician'  => 'Technician',
            'user'        => 'User',
            default       => ucfirst($this->user->role),
        };
    }

    /** Label and pill classes for a report or work order status. */
    public static function statusMeta(?string $status): ?array
    {
        if (! $status) {
            return null;
        }

        $enum = ReportStatus::tryFrom($status) ?? WorkOrderStatus::tryFrom($status);

        return $enum
            ? ['label' => $enum->label(), 'badge' => $enum->badge()]
            : ['label' => Str::headline($status), 'badge' => 'bg-[#f5f7fb] text-[#64748b]'];
    }

    /** Keys: q, role, action, period. */
    public function scopeFilter(Builder $query, array $f): Builder
    {
        $from = match ($f['period'] ?? null) {
            '7d'    => now()->subDays(6)->startOfDay(),
            '30d'   => now()->subDays(29)->startOfDay(),
            '90d'   => now()->subDays(89)->startOfDay(),
            default => null,
        };

        return $query
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($f['role'] ?? null, fn ($q, $role) => $q->whereHas('user', fn ($u) => $u->where('role', $role)))
            ->when($f['action'] ?? null, fn ($q, $action) => $q->where('action', $action))
            ->when($f['q'] ?? null, function ($q, $term) {
                $like = '%' . addcslashes($term, '%_\\') . '%';
                $q->where(fn ($w) => $w
                    ->where('description', 'like', $like)
                    ->orWhere('action', 'like', $like)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like)));
            });
    }
}
