<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Building;
use App\Models\Category;
use App\Models\Report;
use App\Models\Technician;
use App\Models\User;
use App\Models\WorkOrder;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Read-only: nothing here edits or deletes a log entry. */
class ActivityLogController extends Controller
{
    private const PER_PAGE = [8, 16, 32];
    private const ROLES    = ['admin' => 'Administrator', 'technician' => 'Technician'];

    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $perPage = in_array((int) $request->query('per_page'), self::PER_PAGE, true) ? (int) $request->query('per_page') : self::PER_PAGE[0];

        $logs = ActivityLog::query()
            ->with(['user', 'subject'])
            ->filter($filters)
            ->latest()->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        $tz = config('app.timezone');

        return view('admin.activity-logs.index', [
            'logs'       => $logs,
            'filters'    => $filters,
            'perPage'    => $perPage,
            'perPageOptions' => self::PER_PAGE,
            'hasFilters' => collect($filters)->except('period')->filter()->isNotEmpty() || $filters['period'] !== '7d',
            'timezone'   => $tz === 'Asia/Manila' ? 'Philippine Standard Time' : $tz,
            'options'    => [
                'role'   => ['' => 'All roles'] + self::ROLES,
                'action' => ['' => 'All actions'] + $this->actionOptions(),
                'period' => $this->periodOptions(),
            ],
            'reassignment' => ActivityLog::where('action', 'work_order.reassigned')->latest()->latest('id')->value('description'),
            'target'     => fn ($log) => $this->target($log),
        ]);
    }

    /** No button in the design, but the current filter can still be downloaded from /admin/activity-logs/export. */
    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);

        return response()->streamDownload(function () use ($filters) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Time', 'User', 'Role', 'Action', 'Previous status', 'New status', 'Description', 'IP address']);

            ActivityLog::query()->with('user')->filter($filters)->orderBy('id')->chunk(500, function ($chunk) use ($out) {
                foreach ($chunk as $log) {
                    fputcsv($out, [
                        $log->created_at->format('Y-m-d H:i:s'),
                        $log->user?->name ?? 'System',
                        $log->roleLabel(),
                        $log->label(),
                        ActivityLog::statusMeta($log->from_status)['label'] ?? '',
                        ActivityLog::statusMeta($log->to_status)['label'] ?? '',
                        $log->description,
                        $log->ip_address,
                    ]);
                }
            });

            fclose($out);
        }, 'mcc-assetwatch-activity-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** [label, url|null] for the record an entry is about. */
    private function target(ActivityLog $log): array
    {
        $subject = $log->subject;
        if (! $subject) {
            return ['—', null];
        }

        $link = fn (string $route, $param = null) => Route::has($route) ? ($param ? route($route, $param) : route($route)) : null;

        return match (true) {
            $subject instanceof Report     => [$subject->reference_no, $link('admin.reports.show', $subject)],
            $subject instanceof WorkOrder  => [$subject->reference_no, $link('admin.work-orders.show', $subject)],
            $subject instanceof Technician => [$subject->staff_id, $link('admin.personnel.show', $subject)],
            $subject instanceof User       => [$subject->email, $link('admin.users.index')],
            $subject instanceof Building   => [$subject->code, $link('admin.locations.index')],
            $subject instanceof Category   => [$subject->name, $link('admin.categories.index')],
            default                        => [class_basename($subject) . ' #' . $subject->getKey(), null],
        };
    }

    /** Only actions that exist in the log, in friendly wording. */
    private function actionOptions(): array
    {
        return ActivityLog::query()->distinct()->orderBy('action')->pluck('action')
            ->mapWithKeys(fn ($action) => [$action => ActivityLog::labelFor($action)])
            ->sort()
            ->all();
    }

    private function periodOptions(): array
    {
        $label = function (CarbonInterface $from) {
            $to = now();

            return $from->isSameMonth($to) ? $from->format('j') . '–' . $to->format('j M Y') : $from->format('j M') . '–' . $to->format('j M Y');
        };

        return [
            '7d'  => $label(now()->subDays(6)),
            '30d' => $label(now()->subDays(29)),
            '90d' => $label(now()->subDays(89)),
            'all' => 'All time',
        ];
    }

    private function filters(Request $request): array
    {
        $action = (string) $request->query('action');

        return [
            'q'      => trim((string) $request->query('q', '')) ?: null,
            'role'   => array_key_exists((string) $request->query('role'), self::ROLES) ? (string) $request->query('role') : null,
            'action' => ($action !== '' && ActivityLog::where('action', $action)->exists()) ? $action : null,
            'period' => in_array($request->query('period'), ['7d', '30d', '90d', 'all'], true) ? $request->query('period') : '7d',
        ];
    }
}
