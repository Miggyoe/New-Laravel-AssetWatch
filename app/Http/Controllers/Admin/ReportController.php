<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReportPriority;
use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Category;
use App\Models\Report;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $perPage = in_array((int) $request->query('per_page'), [10, 25, 50], true)
            ? (int) $request->query('per_page')
            : 10;

        $reports = Report::query()
            ->with(['category', 'location.building', 'reporter'])
            ->filter($filters)
            ->latest()
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.reports.index', [
            'reports'    => $reports,
            'filters'    => $filters,
            'perPage'    => $perPage,
            'totalAll'   => Report::count(),
            'hasFilters' => collect($filters)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty(),
            'options'    => [
                'status'   => ['' => 'All statuses'] + collect(ReportStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all(),
                'priority' => ['' => 'All priorities'] + collect(ReportPriority::cases())->mapWithKeys(fn ($p) => [$p->value => $p->label()])->all(),
                'building' => ['' => 'All buildings'] + Building::orderBy('name')->pluck('name', 'id')->all(),
                'category' => ['' => 'All categories'] + Category::orderBy('name')->pluck('name', 'id')->all(),
                'period'   => [
                    ''      => 'All dates',
                    '7d'    => 'Last 7 days',
                    '30d'   => 'Last 30 days',
                    'month' => now()->startOfMonth()->format('d') . '–' . now()->format('d M Y'),
                    'year'  => 'This year',
                ],
            ],
        ]);
    }

    /** CSV of whatever the current filters match (opens cleanly in Excel). */
    public function export(Request $request): StreamedResponse
    {
        $filters  = $this->filters($request);
        $filename = 'mcc-assetwatch-reports-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($filters) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
            fputcsv($out, ['Report ID', 'Incident title', 'Building', 'Room', 'Category', 'Reporter', 'Reporter email', 'Submitted', 'Priority', 'Status']);

            Report::query()
                ->with(['category', 'location.building', 'reporter'])
                ->filter($filters)
                ->orderBy('id')
                ->chunk(500, function ($chunk) use ($out) {
                    foreach ($chunk as $r) {
                        fputcsv($out, [
                            $r->reference_no,
                            $r->title,
                            $r->location?->building?->name,
                            $r->location?->name,
                            $r->category?->name,
                            $r->reporter?->name,
                            $r->reporter?->email,
                            $r->created_at->format('Y-m-d H:i'),
                            $r->priority->label(),
                            $r->status->label(),
                        ]);
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Whitelist and normalise the query string. */
    private function filters(Request $request): array
    {
        $in = fn (string $key, array $allowed) => in_array($request->query($key), $allowed, true) ? $request->query($key) : null;

        return [
            'q'           => trim((string) $request->query('q', '')) ?: null,
            'status'      => $in('status', array_column(ReportStatus::cases(), 'value')),
            'priority'    => $in('priority', array_column(ReportPriority::cases(), 'value')),
            'category_id' => ctype_digit((string) $request->query('category_id')) ? (int) $request->query('category_id') : null,
            'building_id' => ctype_digit((string) $request->query('building_id')) ? (int) $request->query('building_id') : null,
            'period'      => $in('period', ['7d', '30d', 'month', 'year']),
        ];
    }
}
