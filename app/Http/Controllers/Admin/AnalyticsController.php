<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Category;
use App\Models\Report;
use App\Models\Technician;
use App\Models\WorkOrder;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        [$filters, $start] = $this->filters($request);
        $buildingId = $filters['building_id'];

        $base = fn (): Builder => $this->reports($start, $buildingId);

        // ---- Headline numbers
        $total      = $base()->count();
        $resolved   = $base()->where('reports.status', ReportStatus::Resolved->value)->count();
        $rejected   = $base()->where('reports.status', ReportStatus::Rejected->value)->count();
        $turnaround = $this->averageTurnaround($base());

        $previous = null;
        if ($start) {
            $days     = max(1, (int) $start->diffInDays(now()));
            $previous = $this->averageTurnaround($this->reports($start->copy()->subDays($days), $buildingId)->where('reports.created_at', '<', $start));
        }

        // ---- Status distribution (donut)
        $counts   = $base()->selectRaw('reports.status as s, count(*) as c')->groupBy('reports.status')->pluck('c', 's');
        $circumference = 2 * M_PI * 69;
        $offset   = 0;
        $segments = [];
        foreach (ReportStatus::cases() as $status) {
            $count = (int) ($counts[$status->value] ?? 0);
            $len   = $total > 0 ? $count / $total * $circumference : 0;
            $segments[] = ['status' => $status, 'count' => $count, 'dash' => round(max(0, $len - ($total > 0 ? 2 : 0)), 2), 'offset' => round(-$offset, 2)];
            $offset += $len;
        }

        // ---- Categories and buildings
        $categoryNames = Category::pluck('name', 'id');
        $categories = $base()->selectRaw('reports.category_id as k, count(*) as c')->groupBy('reports.category_id')->orderByDesc('c')->get()
            ->map(fn ($r) => ['label' => $categoryNames[$r->k] ?? 'Uncategorised', 'count' => (int) $r->c]);

        $buildings = $base()->join('locations', 'locations.id', '=', 'reports.location_id')
            ->selectRaw('locations.building_id as k, count(*) as c')->groupBy('locations.building_id')->orderByDesc('c')->get();
        $buildingNames = Building::pluck('name', 'id');
        $buildings = $buildings->map(fn ($r) => ['label' => $buildingNames[$r->k] ?? 'Unknown', 'count' => (int) $r->c]);

        return view('admin.analytics.index', [
            'filters'    => $filters,
            'options'    => $this->options(),
            'periodNote' => $this->periodNote($start),
            'total'      => $total,
            'resolved'   => $resolved,
            'rejected'   => $rejected,
            'unresolved' => $total - $resolved,
            'turnaround' => $turnaround,
            'turnaroundDelta' => ($turnaround !== null && $previous !== null) ? round($turnaround - $previous, 1) : null,
            'months'     => $this->monthly($start, $buildingId),
            'categories' => $categories,
            'buildings'  => $buildings,
            'donut'      => ['circumference' => round($circumference, 2), 'segments' => $segments],
            'technicians' => $this->technicians($start, $buildingId),
            'queryString' => $request->query(),
        ]);
    }

    /** One row per report in the current filter (opens cleanly in Excel). */
    public function export(Request $request): StreamedResponse
    {
        [$filters, $start] = $this->filters($request);
        $buildingId = $filters['building_id'];

        return response()->streamDownload(function () use ($start, $buildingId) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Report ID', 'Incident title', 'Building', 'Room', 'Category', 'Priority', 'Status', 'Submitted', 'Resolved', 'Turnaround (days)']);

            $this->reports($start, $buildingId)
                ->with(['category', 'location.building'])
                ->orderBy('reports.id')
                ->chunk(500, function ($chunk) use ($out) {
                    foreach ($chunk as $r) {
                        fputcsv($out, [
                            $r->reference_no,
                            $r->title,
                            $r->location?->building?->name,
                            $r->location?->name,
                            $r->category?->name,
                            $r->priority->label(),
                            $r->status->label(),
                            $r->created_at->format('Y-m-d H:i'),
                            $r->resolved_at?->format('Y-m-d H:i'),
                            $r->resolved_at ? round($r->created_at->diffInSeconds($r->resolved_at) / 86400, 1) : null,
                        ]);
                    }
                });

            fclose($out);
        }, 'mcc-assetwatch-analytics-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ------------------------------------------------------------------ helpers

    private function reports(?CarbonInterface $start, ?int $buildingId): Builder
    {
        return Report::query()
            ->when($start, fn ($q) => $q->where('reports.created_at', '>=', $start))
            ->when($buildingId, fn ($q) => $q->whereHas('location', fn ($l) => $l->where('building_id', $buildingId)));
    }

    /** Mean days from submission to resolution for resolved reports, or null when there are none. */
    private function averageTurnaround(Builder $query): ?float
    {
        $rows = $query->where('reports.status', ReportStatus::Resolved->value)->whereNotNull('reports.resolved_at')->get(['reports.created_at', 'reports.resolved_at']);

        if ($rows->isEmpty()) {
            return null;
        }

        return round($rows->avg(fn ($r) => $r->created_at->diffInSeconds($r->resolved_at) / 86400), 1);
    }

    /** Reports per month, split into resolved and unresolved (the last 12 months at most). */
    private function monthly(?CarbonInterface $start, ?int $buildingId): array
    {
        $first = $start ? Carbon::instance($start)->startOfMonth() : now()->startOfMonth()->subMonths(11);
        if ($first->lt(now()->startOfMonth()->subMonths(11))) {
            $first = now()->startOfMonth()->subMonths(11);
        }

        $rows = $this->reports($first, $buildingId)->get(['reports.created_at', 'reports.status'])
            ->groupBy(fn ($r) => $r->created_at->format('Y-m'));

        $months = [];
        foreach (CarbonPeriod::create($first, '1 month', now()->startOfMonth()) as $month) {
            $group    = $rows->get($month->format('Y-m'), collect());
            $resolved = $group->filter(fn ($r) => $r->status === ReportStatus::Resolved)->count();
            $months[] = ['label' => $month->format('M'), 'total' => $group->count(), 'resolved' => $resolved, 'unresolved' => $group->count() - $resolved];
        }

        return $months;
    }

    private function technicians(?CarbonInterface $start, ?int $buildingId)
    {
        $scope = function ($q) use ($start, $buildingId) {
            return $q->where('status', 'completed')
                ->when($start, fn ($w) => $w->where('completed_at', '>=', $start))
                ->when($buildingId, fn ($w) => $w->whereHas('report.location', fn ($l) => $l->where('building_id', $buildingId)));
        };

        return Technician::query()
            ->where('is_active', true)
            ->withActiveOrders()
            ->withCount(['workOrders as completed_count' => fn ($q) => $scope($q)])
            ->withCount(['workOrders as on_time_count' => fn ($q) => $scope($q)->whereColumn('completed_at', '<=', 'target_at')])
            ->orderByDesc('completed_count')
            ->limit(10)
            ->get();
    }

    /** @return array{0: array{period: string, building_id: ?int}, 1: ?CarbonInterface} */
    private function filters(Request $request): array
    {
        $period = in_array($request->query('period'), ['3m', '6m', '12m', 'year', 'all'], true) ? $request->query('period') : '6m';

        $start = match ($period) {
            '3m'    => now()->startOfMonth()->subMonths(2),
            '6m'    => now()->startOfMonth()->subMonths(5),
            '12m'   => now()->startOfMonth()->subMonths(11),
            'year'  => now()->startOfYear(),
            default => null,
        };

        $building = ctype_digit((string) $request->query('building_id')) ? (int) $request->query('building_id') : null;

        return [['period' => $period, 'building_id' => $building], $start];
    }

    private function options(): array
    {
        $label = fn (CarbonInterface $from) => $from->format('d M') . '–' . now()->format('d M Y');

        return [
            'period' => [
                '6m'   => $label(now()->startOfMonth()->subMonths(5)),
                '3m'   => $label(now()->startOfMonth()->subMonths(2)),
                '12m'  => $label(now()->startOfMonth()->subMonths(11)),
                'year' => $label(now()->startOfYear()),
                'all'  => 'All time',
            ],
            'building' => ['' => 'All campus buildings'] + Building::orderBy('name')->pluck('name', 'id')->all(),
        ];
    }

    private function periodNote(?CarbonInterface $start): string
    {
        if (! $start) {
            return 'All time';
        }

        return $start->isSameMonth(now()) ? now()->format('M Y') : $start->format('M') . '–' . now()->format('M Y');
    }
}
