<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\ReportUpdate;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $range = in_array((int) $request->query('range'), [7, 14, 30], true)
            ? (int) $request->query('range')
            : 7;

        /** @var Collection<string,int> $counts status => total */
        $counts = Report::query()
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $total = (int) $counts->sum();

        return view('admin.dashboard', [
            'range' => $range,
            'stats' => [
                'total'        => $total,
                'pending'      => (int) ($counts[ReportStatus::Pending->value] ?? 0),
                'in_progress'  => (int) ($counts[ReportStatus::InProgress->value] ?? 0),
                'resolved_ytd' => Report::where('status', ReportStatus::Resolved->value)
                                        ->whereYear('resolved_at', now()->year)
                                        ->count(),
            ],
            'trend'    => $this->trend($range),
            'donut'    => $this->donut($counts, $total),
            'total'    => $total,
            'recent'   => Report::with(['category', 'location.building'])->latest()->limit(5)->get(),
            'activity' => ReportUpdate::with(['report', 'user'])->latest()->limit(4)->get(),
        ]);
    }

    /** Received vs resolved per day, pre-computed into SVG coordinates (plot area 30..635 x 10..115). */
    private function trend(int $days): array
    {
        $start = now()->subDays($days - 1)->startOfDay();

        $received = Report::where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) AS d, COUNT(*) AS c')->groupBy('d')->pluck('c', 'd');

        $resolved = Report::whereNotNull('resolved_at')->where('resolved_at', '>=', $start)
            ->selectRaw('DATE(resolved_at) AS d, COUNT(*) AS c')->groupBy('d')->pluck('c', 'd');

        $labels = $rec = $res = [];
        foreach (CarbonPeriod::create($start, now()->startOfDay()) as $day) {
            $key      = $day->toDateString();
            $labels[] = $day->format('j M');
            $rec[]    = (int) ($received[$key] ?? 0);
            $res[]    = (int) ($resolved[$key] ?? 0);
        }

        $n    = count($labels);
        $max  = max(1, max($rec), max($res));
        $step = max(5, (int) (ceil($max / 3 / 5) * 5));
        $top  = $step * 3;

        $x = fn (int $i) => 30 + ($n > 1 ? $i * 605 / ($n - 1) : 0);
        $y = fn (int $v) => 115 - ($v / $top) * 105;
        $points = fn (array $vals) => collect($vals)
            ->map(fn ($v, $i) => round($x($i), 1) . ',' . round($y($v), 1))
            ->implode(' ');

        $every = (int) ceil($n / 7); // keep the date axis readable on 14/30 day ranges

        return [
            'top'             => $top,
            'ticks'           => [0, $step, $step * 2, $top],
            'received_points' => $points($rec),
            'resolved_points' => $points($res),
            'labels'          => collect($labels)->map(fn ($text, $i) => [
                'text' => $text,
                'x'    => round($x($i), 1),
                'show' => (($n - 1 - $i) % $every) === 0,
            ])->all(),
            'period' => $start->isSameMonth(now())
                ? $start->format('j') . '–' . now()->format('j F')
                : $start->format('j M') . ' – ' . now()->format('j M'),
        ];
    }

    /** Donut segments as stroke-dasharray values on a circle of radius 69. */
    private function donut(Collection $counts, int $total): array
    {
        $circumference = 2 * M_PI * 69;
        $gap           = $total > 0 ? 2 : 0;
        $offset        = 0;
        $segments      = [];

        foreach (ReportStatus::cases() as $status) {
            $count = (int) ($counts[$status->value] ?? 0);
            $len   = $total > 0 ? $count / $total * $circumference : 0;

            $segments[] = [
                'status' => $status,
                'count'  => $count,
                'dash'   => round(max(0, $len - $gap), 2),
                'offset' => round(-$offset, 2),
            ];
            $offset += $len;
        }

        return ['circumference' => round($circumference, 2), 'segments' => $segments];
    }
}
