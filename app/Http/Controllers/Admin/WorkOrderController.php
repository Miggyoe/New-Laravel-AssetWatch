<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReportPriority;
use App\Enums\ReportStatus;
use App\Enums\WorkOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Report;
use App\Models\ReportUpdate;
use App\Models\Technician;
use App\Models\WorkOrder;
use App\Models\WorkOrderNote;
use App\Models\WorkOrderUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WorkOrderController extends Controller
{
    private const PER_PAGE = [8, 16, 32];

    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $perPage = in_array((int) $request->query('per_page'), self::PER_PAGE, true)
            ? (int) $request->query('per_page')
            : self::PER_PAGE[0];

        $orders = WorkOrder::query()
            ->with(['report.location.building', 'technician'])
            ->filter($filters)
            ->latest()
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        // Summary cards follow the selected period only (not the other filters)
        $counts = WorkOrder::query()->inPeriod($filters['period'])
            ->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');

        $currentMonth = now()->format('Y-m');
        $periods      = ['all' => 'All time'];
        for ($i = 0; $i < 12; $i++) {
            $month = now()->startOfMonth()->subMonths($i);
            $periods[$month->format('Y-m')] = $month->format('F Y');
        }

        $periodTotal = (int) $counts->sum();
        $periodText  = match (true) {
            $filters['period'] === 'all'         => 'work orders in total',
            $filters['period'] === $currentMonth => 'work orders this month',
            default                              => 'work orders in ' . ($periods[$filters['period']] ?? $filters['period']),
        };

        $hasFilters = collect($filters)->except('period')->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty()
            || $filters['period'] !== $currentMonth;

        // Verified reports that don't have an active work order yet
        $assignable = Report::query()
            ->where('status', ReportStatus::Verified->value)
            ->whereNotIn('id', WorkOrder::where('status', '!=', WorkOrderStatus::Cancelled->value)->select('report_id'))
            ->latest()->limit(200)->get();

        $technicians = Technician::where('is_active', true)->orderBy('name')->get();

        return view('admin.work-orders.index', [
            'orders'      => $orders,
            'filters'     => $filters,
            'perPage'     => $perPage,
            'perPageOptions' => self::PER_PAGE,
            'counts'      => $counts,
            'periodTotal' => $periodTotal,
            'periodText'  => $periodText,
            'hasFilters'  => $hasFilters,
            'options'     => [
                'status'     => ['' => 'All statuses'] + collect(WorkOrderStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all(),
                'technician' => ['' => 'All technicians'] + Technician::orderBy('name')->pluck('name', 'id')->all(),
                'priority'   => ['' => 'All priorities'] + collect(ReportPriority::cases())->mapWithKeys(fn ($p) => [$p->value => $p->label()])->all(),
                'period'     => $periods,
                'assignable' => ['' => 'Select a verified incident'] + $assignable->mapWithKeys(fn ($r) => [$r->id => $r->reference_no . ' · ' . $r->title])->all(),
                'assignee'   => ['' => 'Select technician'] + $technicians->mapWithKeys(fn ($t) => [$t->id => $t->label()])->all(),
                'newPriority' => collect(ReportPriority::cases())->mapWithKeys(fn ($p) => [$p->value => $p->label()])->all(),
            ],
            'hasAssignable' => $assignable->isNotEmpty(),
            'openCreate'    => $request->filled('report') || (old('_form') === 'create' && session()->has('errors')),
            'preselectedReport' => $request->query('report'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'report_id'     => [
                'required',
                Rule::exists('reports', 'id')->where('status', ReportStatus::Verified->value)->whereNull('deleted_at'),
                Rule::unique('work_orders', 'report_id')->where(fn ($q) => $q->where('status', '!=', WorkOrderStatus::Cancelled->value)),
            ],
            'title'         => ['required', 'string', 'max:150'],
            'instructions'  => ['required', 'string', 'max:2000'],
            'technician_id' => ['required', Rule::exists('technicians', 'id')->where('is_active', true)],
            'priority'      => ['required', Rule::enum(ReportPriority::class)],
            'target_date'   => ['required', 'date', 'after_or_equal:today'],
            'notes'         => ['nullable', 'string', 'max:500'],
        ], [
            'report_id.exists' => 'Only verified incident reports can be assigned.',
            'report_id.unique' => 'This report already has an active work order.',
        ]);

        $order = DB::transaction(function () use ($data) {
            $technician = Technician::findOrFail($data['technician_id']);
            $report     = Report::findOrFail($data['report_id']);

            $order = WorkOrder::create([
                'report_id'     => $report->id,
                'technician_id' => $technician->id,
                'created_by'    => auth()->id(),
                'title'         => $data['title'],
                'instructions'  => $data['instructions'],
                'priority'      => $data['priority'],
                'status'        => WorkOrderStatus::Assigned,
                'target_at'     => \Carbon\Carbon::parse($data['target_date'])->setTime(17, 0),
            ]);

            WorkOrderUpdate::create([
                'work_order_id' => $order->id,
                'user_id'       => auth()->id(),
                'status'        => WorkOrderStatus::Assigned->value,
                'note'          => 'Assigned to ' . $technician->name . '.',
            ]);

            if (filled($data['notes'] ?? null)) {
                WorkOrderNote::create(['work_order_id' => $order->id, 'user_id' => auth()->id(), 'body' => $data['notes']]);
            }

            // Verified report moves to In Progress; its original description and evidence stay untouched
            $report->update(['status' => ReportStatus::InProgress]);
            ReportUpdate::create([
                'report_id'   => $report->id,
                'user_id'     => auth()->id(),
                'from_status' => ReportStatus::Verified->value,
                'to_status'   => ReportStatus::InProgress->value,
            ]);

            ActivityLog::record('work_order.created', "Created work order {$order->reference_no} for {$report->reference_no}", $order, null, WorkOrderStatus::Assigned->value);

            return $order;
        });

        return redirect()->route('admin.work-orders.show', $order)
            ->with('status', "Work order {$order->reference_no} created and assigned.");
    }

    public function show(WorkOrder $workOrder)
    {
        $workOrder->load(['report.location.building', 'report.category', 'technician', 'creator', 'reviewer', 'updates.user', 'notes.user']);

        $others = Technician::where('is_active', true)->whereKeyNot($workOrder->technician_id)->orderBy('name')->get();

        return view('admin.work-orders.show', [
            'order'    => $workOrder,
            'assignee' => ['' => 'Select technician'] + $others->mapWithKeys(fn ($t) => [$t->id => $t->label()])->all(),
        ]);
    }

    public function reassign(Request $request, WorkOrder $workOrder)
    {
        abort_if($workOrder->status === WorkOrderStatus::Cancelled, 403, 'Cancelled work orders cannot be reassigned.');

        $data = $request->validate([
            'technician_id' => ['required', Rule::exists('technicians', 'id')->where('is_active', true), Rule::notIn([$workOrder->technician_id])],
        ], ['technician_id.not_in' => 'Choose a different technician.']);

        DB::transaction(function () use ($workOrder, $data) {
            $from   = $workOrder->technician;
            $to     = Technician::findOrFail($data['technician_id']);
            $before = $workOrder->status->value;

            $attributes = ['technician_id' => $to->id];
            if ($workOrder->status->isOpen()) {
                // The new technician has to accept the job again
                $attributes += ['status' => WorkOrderStatus::Assigned, 'accepted_at' => null];
            }
            $workOrder->update($attributes);

            WorkOrderUpdate::create([
                'work_order_id' => $workOrder->id,
                'user_id'       => auth()->id(),
                'status'        => $workOrder->status->value,
                'note'          => "Reassigned from {$from->name} to {$to->name}.",
            ]);

            ActivityLog::record('work_order.reassigned', "Reassigned {$workOrder->reference_no} from {$from->name} to {$to->name}", $workOrder, $before, $workOrder->status->value);
        });

        return redirect()->route('admin.work-orders.show', $workOrder)->with('status', 'Technician reassigned.');
    }

    public function storeNote(Request $request, WorkOrder $workOrder)
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:1000']], ['body.required' => 'Write a note before saving.']);

        WorkOrderNote::create(['work_order_id' => $workOrder->id, 'user_id' => auth()->id(), 'body' => $data['body']]);
        ActivityLog::record('work_order.note_added', "Added a note to {$workOrder->reference_no}", $workOrder);

        return redirect()->route('admin.work-orders.show', $workOrder)->with('status', 'Note saved.');
    }

    /** Whitelist and normalise the query string. Period defaults to the current month, as in the design. */
    private function filters(Request $request): array
    {
        $period = (string) $request->query('period', '');
        $period = ($period === 'all' || preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period)) ? $period : now()->format('Y-m');

        $in = fn (string $key, array $allowed) => in_array($request->query($key), $allowed, true) ? $request->query($key) : null;

        return [
            'q'             => trim((string) $request->query('q', '')) ?: null,
            'status'        => $in('status', array_column(WorkOrderStatus::cases(), 'value')),
            'priority'      => $in('priority', array_column(ReportPriority::cases(), 'value')),
            'technician_id' => ctype_digit((string) $request->query('technician_id')) ? (int) $request->query('technician_id') : null,
            'period'        => $period,
        ];
    }
}
