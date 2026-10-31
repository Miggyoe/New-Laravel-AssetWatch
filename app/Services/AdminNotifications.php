<?php

namespace App\Services;

use App\Models\Report;
use App\Models\User;
use App\Models\WorkOrder;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

/**
 * The bell in the top bar. Updates are worked out from live data (new and pending reports, high-priority incidents,
 * work orders close to their target date, repairs waiting for review) and filtered by the administrator's own
 * preferences from Settings. A notification is "unread" while it is newer than the user's "Mark all read" time.
 */
class AdminNotifications
{
    private const OPEN_REPORT_STATUSES = ['pending', 'verified', 'in_progress'];

    /** @return array{items: Collection<int, array>, unread: int} */
    public function for(User $user): array
    {
        $prefs  = json_decode((string) ($user->notification_preferences ?? ''), true) ?: [];
        $wants  = fn (string $key) => (bool) ($prefs[$key] ?? true);
        $readAt = $user->notifications_read_at ? Carbon::parse($user->notifications_read_at) : null;

        $url    = fn (string $route, $param = null) => Route::has($route) ? ($param ? route($route, $param) : route($route)) : '#';
        $items  = collect();

        if ($wants('new_reports')) {
            Report::query()->where('status', 'pending')->latest()->limit(3)->get()
                ->each(fn (Report $r) => $items->push([
                    'icon'  => 'clipboard-plus',
                    'title' => 'New incident report',
                    'text'  => $r->reference_no . ' · ' . $r->title,
                    'at'    => $r->created_at,
                    'url'   => $url('admin.reports.show', $r),
                ]));

            $pending = Report::query()->where('status', 'pending');
            if (($count = (clone $pending)->count()) > 0) {
                $items->push([
                    'icon'  => 'clock-3',
                    'title' => 'Awaiting verification',
                    'text'  => $count . ' ' . ($count === 1 ? 'report needs' : 'reports need') . ' administrator review.',
                    'at'    => (clone $pending)->latest()->first()?->created_at,
                    'url'   => Route::has('admin.reports.index') ? route('admin.reports.index', ['status' => 'pending']) : '#',
                ]);
            }
        }

        if ($wants('priority_orders')) {
            Report::query()->where('priority', 'high')->whereIn('status', self::OPEN_REPORT_STATUSES)->latest()->limit(2)->get()
                ->each(fn (Report $r) => $items->push([
                    'icon'  => 'triangle-alert',
                    'title' => 'High-priority incident',
                    'text'  => $r->reference_no . ' · ' . $r->title,
                    'at'    => $r->created_at,
                    'url'   => $url('admin.reports.show', $r),
                ]));

            WorkOrder::query()->whereIn('status', ['assigned', 'accepted', 'in_progress'])
                ->where('target_at', '<=', now()->addHours(36))
                ->orderBy('target_at')->limit(3)->get()
                ->each(function (WorkOrder $o) use ($items, $url) {
                    $overdue = $o->target_at->isPast();
                    $day     = $o->target_at->isToday() ? 'today' : ($o->target_at->isTomorrow() ? 'tomorrow' : $o->target_at->format('j M'));

                    $items->push([
                        'icon'  => 'calendar-clock',
                        'title' => $overdue ? 'Work order overdue' : 'Work order nearing due date',
                        'text'  => $o->reference_no . ' · ' . ($overdue ? 'Was due ' : 'Due ') . $day . ', ' . $o->target_at->format('g:i A'),
                        'at'    => $o->target_at->copy()->subDay()->max($o->created_at),
                        'url'   => $url('admin.work-orders.show', $o),
                    ]);
                });
        }

        if ($wants('completed_repairs')) {
            WorkOrder::query()->with('technician')->where('status', 'completed')->whereNull('reviewed_at')
                ->latest('completed_at')->limit(3)->get()
                ->each(fn (WorkOrder $o) => $items->push([
                    'icon'  => 'circle-check',
                    'title' => 'Repair completed',
                    'text'  => $o->reference_no . ' · ' . ($o->technician?->name ?? 'Technician'),
                    'at'    => $o->completed_at ?? $o->updated_at,
                    'url'   => $url('admin.work-orders.show', $o),
                ]));
        }

        $items = $items->filter(fn ($i) => $i['at'])
            ->sortByDesc(fn ($i) => $i['at']->getTimestamp())
            ->take(10)->values()
            ->map(fn ($i) => $i + ['unread' => ! $readAt || $i['at']->gt($readAt)]);

        return ['items' => $items, 'unread' => $items->where('unread', true)->count()];
    }
}
