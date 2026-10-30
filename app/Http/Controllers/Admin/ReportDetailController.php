<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReportPriority;
use App\Enums\ReportStatus;
use App\Enums\WorkOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Location;
use App\Models\Report;
use App\Models\ReportNote;
use App\Models\ReportUpdate;
use App\Models\WorkOrder;
use App\Models\WorkOrderUpdate;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReportDetailController extends Controller
{
    public const REJECTION_REASONS = [
        'Duplicate of an existing report',
        'Insufficient information',
        'Not a facility issue',
        'Outside campus responsibility',
        'Other',
    ];

    public function show(Report $report)
    {
        // These columns were added later, so cast them here rather than editing the Report model
        $report->mergeCasts(['verified_at' => 'datetime', 'rejected_at' => 'datetime']);

        $report->load(['reporter', 'category', 'location.building', 'attachments', 'updates.user']);

        $workOrder = WorkOrder::with(['technician', 'updates'])
            ->where('report_id', $report->id)
            ->where('status', '!=', WorkOrderStatus::Cancelled->value)
            ->latest('id')->first();

        $nextStatus = match ($report->status) {
            ReportStatus::Verified   => ReportStatus::InProgress,
            ReportStatus::InProgress => ReportStatus::Resolved,
            default                  => null,
        };

        $verifiedUpdate = $report->updates->firstWhere('to_status', 'verified');
        $resolvedUpdate = $report->updates->firstWhere('to_status', 'resolved');
        $rejectedUpdate = $report->updates->firstWhere('to_status', 'rejected');
        $startedAt      = $workOrder?->updates->firstWhere('status', 'in_progress')?->created_at ?? $workOrder?->accepted_at;

        $headerNote = match ($report->status) {
            ReportStatus::Pending    => $report->created_at->format('j M Y') . ' · Before verification',
            ReportStatus::Verified   => ($report->verified_at ?? $verifiedUpdate?->created_at ?? $report->updated_at)->format('j M Y') . ' · After verification',
            ReportStatus::InProgress => ($startedAt ?? $report->updated_at)->format('j M Y') . ' · Repair underway',
            ReportStatus::Resolved   => ($report->resolved_at ?? $resolvedUpdate?->created_at ?? $report->updated_at)->format('j M Y') . ' · Repair completed',
            ReportStatus::Rejected   => ($report->rejected_at ?? $rejectedUpdate?->created_at ?? $report->updated_at)->format('j M Y') . ' · Report rejected',
        };

        // People named on the right-hand cards
        $closedBy   = $report->resolved_by ? \App\Models\User::find($report->resolved_by) : $resolvedUpdate?->user;
        $rejectedBy = $report->rejected_by ? \App\Models\User::find($report->rejected_by) : $rejectedUpdate?->user;

        $resolutionNotes = $report->resolution_notes
            ?: $resolvedUpdate?->remarks
            ?: $workOrder?->updates->where('status', 'completed')->last()?->note;

        return view('admin.reports.show', [
            'report'          => $report,
            'workOrder'       => $workOrder,
            'nextStatus'      => $nextStatus,
            'headerNote'      => $headerNote,
            'timeline'        => $this->timeline($report, $workOrder),
            'notes'           => ReportNote::with('user')->where('report_id', $report->id)->latest()->latest('id')->get(),
            'closedBy'        => $closedBy,
            'rejectedBy'      => $rejectedBy,
            'resolutionNotes' => $resolutionNotes,
            'openModal'       => old('_modal'),
            'options'         => [
                'priority'  => collect(ReportPriority::cases())->mapWithKeys(fn ($p) => [$p->value => $p->label()])->all(),
                'rejection' => collect(self::REJECTION_REASONS)->mapWithKeys(fn ($r) => [$r => $r])->all(),
                'category'  => Category::orderBy('name')->pluck('name', 'id')->all(),
                'location'  => Location::with('building')->get()
                    ->sortBy(fn ($l) => ($l->building?->name ?? '') . $l->name)
                    ->mapWithKeys(fn ($l) => [$l->id => trim(($l->building?->name ?? '') . ' · ' . $l->name, ' ·')])->all(),
            ],
        ]);
    }

    /** Pending only: administrators may correct the original report before it is verified. */
    public function update(Request $request, Report $report): RedirectResponse
    {
        if ($report->status !== ReportStatus::Pending) {
            return back()->with('error', 'The original report is locked after verification.');
        }

        $data = $request->validate([
            'title'       => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:3000'],
            'category_id' => ['required', Rule::exists('categories', 'id')],
            'location_id' => ['required', Rule::exists('locations', 'id')],
        ]);

        $report->update($data);
        ActivityLog::record('report.corrected', "Corrected the original report {$report->reference_no}", $report);

        return back()->with('status', 'Original report updated.');
    }

    public function verify(Report $report): RedirectResponse
    {
        if ($report->status !== ReportStatus::Pending) {
            return back()->with('error', 'Only pending reports can be verified.');
        }

        DB::transaction(function () use ($report) {
            $report->update(['status' => ReportStatus::Verified, 'verified_by' => auth()->id(), 'verified_at' => now()]);

            ReportUpdate::create([
                'report_id'   => $report->id,
                'user_id'     => auth()->id(),
                'from_status' => ReportStatus::Pending->value,
                'to_status'   => ReportStatus::Verified->value,
                'remarks'     => 'Verified by administrator.',
            ]);

            ActivityLog::record('report.verified', "Verified report {$report->reference_no}", $report, ReportStatus::Pending->value, ReportStatus::Verified->value);
        });

        return back()->with('status', "{$report->reference_no} verified. The original report is now locked.");
    }

    public function reject(Request $request, Report $report): RedirectResponse
    {
        if ($report->status !== ReportStatus::Pending) {
            return back()->with('error', 'Only pending reports can be rejected.');
        }

        $data = $request->validate([
            'rejection_reason'      => ['required', Rule::in(self::REJECTION_REASONS)],
            'rejection_explanation' => ['required', 'string', 'max:1000'],
        ], ['rejection_explanation.required' => 'Explain the decision to the reporter.']);

        DB::transaction(function () use ($report, $data) {
            $report->update($data + ['status' => ReportStatus::Rejected, 'rejected_by' => auth()->id(), 'rejected_at' => now()]);

            ReportUpdate::create([
                'report_id'   => $report->id,
                'user_id'     => auth()->id(),
                'from_status' => ReportStatus::Pending->value,
                'to_status'   => ReportStatus::Rejected->value,
                'remarks'     => $data['rejection_reason'] . ': ' . $data['rejection_explanation'],
            ]);

            ActivityLog::record('report.rejected', "Rejected report {$report->reference_no} ({$data['rejection_reason']})", $report, ReportStatus::Pending->value, ReportStatus::Rejected->value);
        });

        return back()->with('status', "{$report->reference_no} rejected. The reason was recorded in the audit log.");
    }

    public function priority(Request $request, Report $report): RedirectResponse
    {
        if (in_array($report->status, [ReportStatus::Resolved, ReportStatus::Rejected], true)) {
            return back()->with('error', 'Priority cannot change on a closed report.');
        }

        $data = $request->validate(['priority' => ['required', Rule::enum(ReportPriority::class)]]);

        $from = $report->priority;
        $report->update($data);
        ActivityLog::record('report.priority_changed', "Changed priority of {$report->reference_no} from {$from->label()} to {$report->priority->label()}", $report);

        return back()->with('status', 'Priority updated.');
    }

    public function status(Request $request, Report $report): RedirectResponse
    {
        $next = match ($report->status) {
            ReportStatus::Verified   => ReportStatus::InProgress,
            ReportStatus::InProgress => ReportStatus::Resolved,
            default                  => null,
        };

        if (! $next) {
            return back()->with('error', 'This report has no further status to move to.');
        }

        $resolving = $next === ReportStatus::Resolved;

        $data = $request->validate([
            'notes'        => ['required', 'string', 'max:1000'],
            'completed_at' => [Rule::requiredIf($resolving), 'nullable', 'date', 'before_or_equal:now'],
        ], ['notes.required' => $resolving ? 'Resolution notes are required.' : 'Add a note for this status change.']);

        DB::transaction(function () use ($report, $next, $resolving, $data) {
            $from = $report->status;
            $at   = $resolving ? Carbon::parse($data['completed_at']) : now();

            $attributes = ['status' => $next];
            if ($resolving) {
                $attributes += ['resolved_at' => $at, 'resolved_by' => auth()->id(), 'resolution_notes' => $data['notes']];
            }
            $report->update($attributes);

            ReportUpdate::create([
                'report_id'   => $report->id,
                'user_id'     => auth()->id(),
                'from_status' => $from->value,
                'to_status'   => $next->value,
                'remarks'     => $data['notes'],
            ]);

            // Closing the incident also closes any linked work order that is still open or unreviewed
            if ($resolving) {
                WorkOrder::where('report_id', $report->id)
                    ->where('status', '!=', WorkOrderStatus::Cancelled->value)
                    ->get()
                    ->each(function (WorkOrder $order) use ($at, $data) {
                        if ($order->status !== WorkOrderStatus::Completed) {
                            $order->update(['status' => WorkOrderStatus::Completed, 'progress' => 100, 'completed_at' => $at]);
                            WorkOrderUpdate::create([
                                'work_order_id' => $order->id,
                                'user_id'       => auth()->id(),
                                'status'        => WorkOrderStatus::Completed->value,
                                'note'          => 'Closed with the incident report: ' . $data['notes'],
                            ]);
                        }
                        if (! $order->reviewed_at) {
                            $order->update(['reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
                        }
                    });
            }

            ActivityLog::record('report.status_changed', "Changed {$report->reference_no} from {$from->label()} to {$next->label()}", $report, $from->value, $next->value);
        });

        return back()->with('status', "{$report->reference_no} is now {$next->label()}.");
    }

    public function storeNote(Request $request, Report $report): RedirectResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:1000']], ['body.required' => 'Write a note before saving.']);

        ReportNote::create(['report_id' => $report->id, 'user_id' => auth()->id(), 'body' => $data['body']]);
        ActivityLog::record('report.note_added', "Added a note to {$report->reference_no}", $report);

        return back()->with('status', 'Note added.');
    }

    /** Submitted → verified → work order assigned → repair started → resolved, newest last. */
    private function timeline(Report $report, ?WorkOrder $workOrder): Collection
    {
        $events = collect([[
            'label' => 'Report submitted',
            'at'    => $report->created_at,
            'by'    => $report->reporter?->name,
        ]]);

        foreach ($report->updates as $update) {
            $label = match ($update->to_status) {
                'verified' => 'Report verified',
                'rejected' => 'Report rejected',
                'resolved' => 'Repair resolved',
                default    => null,   // "pending" is the submission itself; "in progress" is shown through the work order
            };

            if ($label) {
                $events->push(['label' => $label, 'at' => $update->created_at, 'by' => $update->user?->name]);
            }
        }

        if ($workOrder) {
            $events->push(['label' => "{$workOrder->reference_no} assigned", 'at' => $workOrder->created_at, 'by' => $workOrder->technician?->name]);

            $started = $workOrder->updates->firstWhere('status', 'in_progress');
            if ($started) {
                $events->push(['label' => 'Repair started', 'at' => $started->created_at, 'by' => $workOrder->technician?->name]);
            }
        }

        return $events->sortBy(fn ($e) => $e['at']->getTimestamp())->values();
    }
}
