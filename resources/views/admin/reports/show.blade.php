@extends('layouts.admin')

@section('title', $report->reference_no)
@section('breadcrumb', 'Incident Reports')

@section('content')
    @php
        $RS         = \App\Enums\ReportStatus::class;
        $status     = $report->status;
        $isPending  = $status === $RS::Pending;
        $isRejected = $status === $RS::Rejected;
        $isResolved = $status === $RS::Resolved;
        $isActive   = in_array($status, [$RS::Verified, $RS::InProgress], true);
        $attachments = $report->attachments;
        $first       = $attachments->first();

        $btn     = 'flex h-9 w-full items-center justify-center rounded-md text-[11px] font-semibold transition-colors';
        $primary = "$btn bg-brand text-white hover:bg-blue-700";
        $outline = "$btn border border-line bg-white text-ink hover:bg-canvas";
        $danger  = "$btn border border-[#b91c1c] bg-white text-[#b91c1c] hover:bg-[#fef2f2]";

        $createUrl = route('admin.work-orders.index', ['report' => $report->id]);
        $orderUrl  = $workOrder ? route('admin.work-orders.show', $workOrder) : null;
    @endphp

    {{-- Page heading --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-col gap-[5px] leading-[1.45]">
            <h1 class="text-[26px] font-semibold">{{ $report->title }}</h1>
            <p class="text-xs text-muted">{{ $report->reference_no }} · Incident report details</p>
        </div>
        <div class="flex items-center gap-2.5">
            <p class="text-[11px] text-muted">{{ $headerNote }}</p>
            <a href="{{ route('admin.reports.index') }}" class="rounded-md bg-brand px-4 py-2.5 text-xs font-medium text-white hover:bg-blue-700">Back to Reports</a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-[22px] xl:grid-cols-[736fr_402fr]">
        {{-- Left: original report + timeline --}}
        <div class="flex min-w-0 flex-col gap-[18px]">
            <section class="flex flex-col gap-3.5 rounded-[10px] border border-line bg-white p-5 shadow-[0_2px_3px_rgba(23,54,93,0.02)]">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-status-badge :status="$status" />
                        <x-priority-badge :priority="$report->priority" />
                        @unless ($isPending)
                            <span class="flex items-center gap-[5px] text-[10px] text-muted"><x-icon name="lock-keyhole" :size="13" />Report Locked</span>
                        @endunless
                    </div>
                    @if ($isPending)
                        <button type="button" data-modal-open="edit-original" class="text-[11px] font-medium text-brand hover:underline">Edit original report</button>
                    @endif
                </div>

                @if ($isRejected)
                    <x-alert tone="red" icon="lock-keyhole" title="Report rejected — original submission preserved"
                             :body="'Reason: ' . $report->rejection_reason" />
                @elseif (! $isPending)
                    <x-alert tone="amber" icon="lock-keyhole" title="Report Locked — original submission preserved"
                             body="Description, location, and evidence are immutable. Reporter editing and deletion are disabled; authorized staff can manage workflow and notes." />
                @endif

                <div class="flex flex-col gap-1.5 leading-[1.45]">
                    <p class="text-[11px] font-semibold">Incident description</p>
                    <p class="whitespace-pre-line text-xs">{{ $report->description }}</p>
                </div>

                <div class="flex flex-col gap-4 sm:flex-row sm:gap-6">
                    <x-detail label="Asset category" :value="$report->category?->name ?? '—'" />
                    <x-detail label="Building / Room" :value="$report->placeLabel()" />
                    <x-detail label="Submitted" :value="$report->created_at->format('j M Y · g:i A')" />
                </div>

                <div class="flex flex-col gap-4 sm:flex-row sm:gap-6">
                    <x-detail label="Reporter" :value="($report->reporter?->name ?? '—') . ($report->reporter?->department ? ' · ' . $report->reporter->department : '')" />
                    <x-detail label="Institutional email" :value="$report->reporter?->email ?? '—'" />
                </div>

                {{-- Original evidence --}}
                <div class="flex flex-col gap-[9px]">
                    <div class="flex items-start justify-between text-[11px] leading-[1.45]">
                        <p class="font-semibold">Original evidence · {{ $attachments->count() }} {{ \Illuminate\Support\Str::plural('attachment', $attachments->count()) }}</p>
                        @if ($first)
                            <button type="button" data-modal-open="evidence-{{ $first->id }}" class="font-medium text-brand hover:underline">Expand evidence ↗</button>
                        @endif
                    </div>

                    @if ($first)
                        <div class="relative w-full overflow-hidden rounded-md bg-canvas" style="height: {{ $isPending ? 230 : 172 }}px">
                            @if ($first->previewUrl())
                                <img src="{{ $first->previewUrl() }}" alt="{{ $first->displayName() }}" class="size-full object-cover">
                            @else
                                <div class="flex size-full flex-col items-center justify-center gap-1.5 text-muted">
                                    <x-icon name="image" :size="28" />
                                    <p class="text-[11px]">Preview unavailable — open the original in Google Drive</p>
                                </div>
                            @endif
                        </div>
                        <div class="flex flex-wrap items-start justify-between gap-2 leading-[1.45]">
                            <p class="text-[10px] text-muted">
                                {{ $first->displayName() }}{{ $first->sizeLabel() ? ' · ' . $first->sizeLabel() : '' }} · Submitted {{ $report->created_at->format('j M Y') }}
                            </p>
                            @if ($first->safeDriveUrl())
                                <a href="{{ $first->safeDriveUrl() }}" target="_blank" rel="noopener noreferrer" class="text-[11px] font-medium text-brand hover:underline">Open in Google Drive ↗</a>
                            @endif
                        </div>

                        @foreach ($attachments->skip(1) as $extra)
                            <button type="button" data-modal-open="evidence-{{ $extra->id }}" class="text-left text-[11px] font-medium text-brand hover:underline">
                                {{ $extra->displayName() }}{{ $extra->sizeLabel() ? ' · ' . $extra->sizeLabel() : '' }} ↗
                            </button>
                        @endforeach
                    @else
                        <p class="text-xs text-muted">No evidence was attached to this report.</p>
                    @endif
                </div>
            </section>

            <section class="flex flex-col gap-3.5 rounded-[10px] border border-line bg-white p-[18px] shadow-[0_2px_3px_rgba(23,54,93,0.02)]">
                <h2 class="text-[15px] font-semibold leading-[1.45]">Report timeline</h2>
                <div class="flex flex-col">
                    @foreach ($timeline as $event)
                        <p class="leading-[1.8]">
                            <span class="text-[11px] text-teal">●&nbsp;&nbsp;</span><span class="text-[11px] font-medium">{{ $event['label'] }}</span><span class="text-[10px] text-muted">&nbsp;&nbsp;—&nbsp;&nbsp;{{ $event['at']->format('j M, g:i A') }}{{ $event['by'] ? ' • ' . $event['by'] : '' }}</span>
                        </p>
                    @endforeach
                </div>
            </section>
        </div>

        {{-- Right: workflow --}}
        <div class="flex min-w-0 flex-col gap-[18px]">
            @if ($isResolved)
                <section class="flex flex-col gap-3.5 rounded-[10px] border border-line bg-white p-5 shadow-[0_2px_3px_rgba(23,54,93,0.02)]">
                    <div class="leading-[1.45]">
                        <h2 class="text-[15px] font-semibold">Resolution details</h2>
                        <p class="text-[11px] text-muted">Repair reviewed and closed{{ $closedBy ? ' by ' . $closedBy->name : '' }}</p>
                    </div>
                    <x-alert tone="teal" icon="circle-check" title="Repair completed and verified" />
                    <x-detail label="Completed" :value="($report->resolved_at ?? $report->updated_at)->format('j M Y · g:i A')" :grow="false" />
                    @if ($workOrder)
                        <x-detail label="Work order / Technician" :value="$workOrder->reference_no . ' · ' . $workOrder->technician?->name" :grow="false" />
                    @endif
                    @if ($resolutionNotes)
                        <p class="whitespace-pre-line text-xs leading-[1.45]">{{ $resolutionNotes }}</p>
                    @endif
                    @if ($workOrder?->evidence_url && str_starts_with($workOrder->evidence_url, 'https://'))
                        <a href="{{ $workOrder->evidence_url }}" target="_blank" rel="noopener noreferrer" class="text-[11px] font-medium text-brand hover:underline">View completion evidence ↗</a>
                    @endif
                    @if ($orderUrl)
                        <a href="{{ $orderUrl }}" class="{{ $outline }}">View Work Order</a>
                    @endif
                </section>
            @elseif ($isRejected)
                <section class="flex flex-col gap-3.5 rounded-[10px] border border-line bg-white p-5 shadow-[0_2px_3px_rgba(23,54,93,0.02)]">
                    <div class="leading-[1.45]">
                        <h2 class="text-[15px] font-semibold">Rejection details</h2>
                        <p class="text-[11px] text-muted">Rejected{{ $rejectedBy ? ' by ' . $rejectedBy->name : '' }}{{ $report->rejected_at ? ' · ' . $report->rejected_at->format('j M Y, g:i A') : '' }}</p>
                    </div>
                    <x-detail label="Reason" :value="$report->rejection_reason ?? '—'" :grow="false" />
                    <div class="leading-[1.6]">
                        <p class="text-[10px] text-muted">Explanation sent to the reporter</p>
                        <p class="whitespace-pre-line text-xs">{{ $report->rejection_explanation ?? '—' }}</p>
                    </div>
                </section>
            @else
                <section class="flex flex-col gap-3.5 rounded-[10px] border border-line bg-white p-5 shadow-[0_2px_3px_rgba(23,54,93,0.02)]">
                    <div class="leading-[1.45]">
                        <h2 class="text-[15px] font-semibold">Administrative actions</h2>
                        <p class="text-[11px] text-muted">Administrator access · Workflow controls</p>
                    </div>

                    @if ($isPending)
                        <p class="text-xs leading-[1.45] text-muted">Review the original report and evidence before verifying or rejecting this incident.</p>

                        <form method="POST" action="{{ route('admin.reports.verify', $report) }}">
                            @csrf
                            <button type="submit" class="{{ $primary }}">Verify Report</button>
                        </form>
                        <button type="button" data-modal-open="reject-report" class="{{ $danger }}">Reject Report</button>
                        <button type="button" data-modal-open="change-priority" class="{{ $outline }}">Change Priority</button>

                        <x-alert tone="blue" icon="info" title="Original report is editable"
                                 body="Authorized administrators may correct information before verification." />
                    @else
                        @if ($orderUrl)
                            <a href="{{ $orderUrl }}" class="{{ $primary }}">View Work Order</a>
                            <a href="{{ $orderUrl }}" class="{{ $outline }}">Assign Maintenance Personnel</a>
                        @else
                            <a href="{{ $createUrl }}" class="{{ $primary }}">Create Work Order</a>
                            <a href="{{ $createUrl }}" class="{{ $outline }}">Assign Maintenance Personnel</a>
                        @endif

                        @if ($nextStatus)
                            <button type="button" data-modal-open="change-status" class="{{ $outline }}">Update Status</button>
                        @endif
                        <button type="button" data-modal-open="change-priority" class="{{ $outline }}">Change Priority</button>

                        <x-detail label="Assigned technician" :value="$workOrder ? $workOrder->technician?->label() : 'Not yet assigned'" :grow="false" />
                        <x-detail label="Target completion" :value="$workOrder ? $workOrder->target_at->format('j M Y · g:i A') : 'Set when creating a work order'" :grow="false" />
                    @endif
                </section>
            @endif

            <section class="flex flex-col gap-3.5 rounded-[10px] border border-line bg-white p-5 shadow-[0_2px_3px_rgba(23,54,93,0.02)]">
                <h2 class="text-[15px] font-semibold leading-[1.45]">Maintenance notes</h2>

                @foreach ($notes as $note)
                    <div class="rounded-md bg-canvas p-3 leading-[1.45]">
                        <p class="whitespace-pre-line text-xs">{{ $note->body }}</p>
                        <p class="mt-1 text-[10px] text-muted">{{ $note->user?->name ?? 'Administrator' }} · {{ $note->created_at->format('j M, g:i A') }}</p>
                    </div>
                @endforeach

                <form method="POST" action="{{ route('admin.reports.notes.store', $report) }}" class="flex flex-col gap-3.5">
                    @csrf
                    <input type="hidden" name="_modal" value="">
                    <x-textarea name="body" label="Internal note" rows="3" maxlength="1000" placeholder="Add context for the maintenance team..." />
                    <button type="submit" class="{{ $outline }}">Add Note</button>
                </form>
                <p class="text-[10px] leading-[1.45] text-muted">Notes are timestamped and visible to authorized staff. They do not modify the original submission.</p>
            </section>
        </div>
    </div>

    {{-- ===== Modals ===== --}}

    @if ($isPending)
        <x-modal id="reject-report" title="Reject this report?" :subtitle="$report->reference_no . ' · ' . $report->title" :open="$openModal === 'reject-report'">
            <form method="POST" action="{{ route('admin.reports.reject', $report) }}" class="flex flex-col gap-5">
                @csrf
                <input type="hidden" name="_modal" value="reject-report">
                <x-alert tone="amber" icon="lock-keyhole" title="This action notifies the reporter"
                         body="A rejection reason is required and will be recorded in the audit log." />
                <x-select name="rejection_reason" label="Rejection reason" required :submit="false" width="w-full"
                          :options="$options['rejection']" :selected="old('rejection_reason', 'Duplicate of an existing report')" />
                <x-textarea name="rejection_explanation" label="Explanation for reporter *" rows="3" maxlength="1000"
                            placeholder="Tell the reporter why this report cannot proceed." />
                <div class="flex justify-end gap-2.5">
                    <button type="button" data-modal-close class="h-9 rounded-md border border-line bg-white px-4 text-[11px] font-semibold text-ink hover:bg-canvas">Keep Report</button>
                    <button type="submit" class="h-9 rounded-md bg-[#b91c1c] px-4 text-[11px] font-semibold text-white hover:bg-[#991b1b]">Reject Report</button>
                </div>
            </form>
        </x-modal>

        <x-modal id="edit-original" title="Edit original report" :subtitle="$report->reference_no . ' · Before verification'" :width="640" :open="$openModal === 'edit-original'">
            <form method="POST" action="{{ route('admin.reports.update', $report) }}" class="flex flex-col gap-5">
                @csrf
                @method('PATCH')
                <input type="hidden" name="_modal" value="edit-original">
                <x-input name="title" label="Incident title" :value="$report->title" required maxlength="150" />
                <x-textarea name="description" label="Incident description" :value="$report->description" rows="4" required maxlength="3000" />
                <div class="flex flex-col gap-4 sm:flex-row">
                    <x-select name="category_id" label="Asset category" required :submit="false" width="w-full sm:flex-1"
                              :options="$options['category']" :selected="old('category_id', $report->category_id)" />
                    <x-select name="location_id" label="Building / Room" required :submit="false" width="w-full sm:flex-1"
                              :options="$options['location']" :selected="old('location_id', $report->location_id)" />
                </div>
                <x-alert tone="blue" icon="info" title="Corrections are recorded" body="After verification the original report becomes read-only." />
                <div class="flex justify-end gap-2.5">
                    <button type="button" data-modal-close class="h-9 rounded-md border border-line bg-white px-4 text-[11px] font-semibold text-ink hover:bg-canvas">Cancel</button>
                    <button type="submit" class="h-9 rounded-md bg-brand px-4 text-[11px] font-semibold text-white hover:bg-blue-700">Save Changes</button>
                </div>
            </form>
        </x-modal>
    @endif

    @if (! $isResolved && ! $isRejected)
        <x-modal id="change-priority" title="Change priority" :subtitle="$report->reference_no . ' · ' . $report->title" :width="480" :open="$openModal === 'change-priority'">
            <form method="POST" action="{{ route('admin.reports.priority', $report) }}" class="flex flex-col gap-5">
                @csrf
                @method('PATCH')
                <input type="hidden" name="_modal" value="change-priority">
                <x-select name="priority" label="Priority" required :submit="false" width="w-full"
                          :options="$options['priority']" :selected="old('priority', $report->priority->value)" />
                <div class="flex justify-end gap-2.5">
                    <button type="button" data-modal-close class="h-9 rounded-md border border-line bg-white px-4 text-[11px] font-semibold text-ink hover:bg-canvas">Cancel</button>
                    <button type="submit" class="h-9 rounded-md bg-brand px-4 text-[11px] font-semibold text-white hover:bg-blue-700">Save Priority</button>
                </div>
            </form>
        </x-modal>
    @endif

    @if ($nextStatus)
        @php $resolving = $nextStatus === $RS::Resolved; @endphp
        <x-modal id="change-status" title="Confirm status change" :subtitle="$report->reference_no . ' · ' . $report->title" :open="$openModal === 'change-status'">
            <form method="POST" action="{{ route('admin.reports.status', $report) }}" class="flex flex-col gap-5">
                @csrf
                @method('PATCH')
                <input type="hidden" name="_modal" value="change-status">

                <div class="flex items-center gap-4">
                    <x-status-badge :status="$status" />
                    <x-icon name="arrow-right" :size="14" class="text-muted" />
                    <x-status-badge :status="$nextStatus" />
                </div>

                <x-alert tone="blue" icon="info" title="Original report remains locked" body="Only the workflow status and resolution notes will change." />

                <x-textarea name="notes" :label="$resolving ? 'Resolution notes *' : 'Status notes *'" rows="3" maxlength="1000"
                            :placeholder="$resolving ? 'Describe the repair that was completed.' : 'Why is the status changing?'" />

                @if ($resolving)
                    <x-input name="completed_at" type="datetime-local" label="Completion date" required :value="now()->format('Y-m-d\TH:i')" />
                @endif

                <div class="flex justify-end gap-2.5">
                    <button type="button" data-modal-close class="h-9 rounded-md border border-line bg-white px-4 text-[11px] font-semibold text-ink hover:bg-canvas">Cancel</button>
                    <button type="submit" class="h-9 rounded-md bg-brand px-4 text-[11px] font-semibold text-white hover:bg-blue-700">Confirm Status Change</button>
                </div>
            </form>
        </x-modal>
    @endif

    {{-- Evidence viewer (one per attachment, read-only) --}}
    @foreach ($attachments as $file)
        <x-modal :id="'evidence-' . $file->id" title="Evidence viewer" :subtitle="$report->reference_no . ' · Original submitted evidence · Read-only'" :width="900">
            <div class="w-full overflow-hidden rounded-md bg-canvas" style="height: 475px">
                @if ($file->previewUrl())
                    <img src="{{ $file->previewUrl() }}" alt="{{ $file->displayName() }}" class="size-full object-cover">
                @else
                    <div class="flex size-full flex-col items-center justify-center gap-2 text-muted">
                        <x-icon name="image" :size="40" />
                        <p class="text-xs">Preview unavailable — open the original in Google Drive</p>
                    </div>
                @endif
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="leading-[1.45]">
                    <p class="text-xs font-medium">{{ $file->displayName() }}</p>
                    <p class="text-[11px] text-muted">
                        {{ $report->reporter?->name ?? 'Reporter' }} · {{ $report->created_at->format('j M Y, g:i A') }}{{ $file->sizeLabel() ? ' · ' . $file->sizeLabel() : '' }}
                    </p>
                </div>
                @if ($file->safeDriveUrl())
                    <a href="{{ $file->safeDriveUrl() }}" target="_blank" rel="noopener noreferrer"
                       class="flex h-9 items-center rounded-md border border-line bg-white px-4 text-[11px] font-semibold text-ink hover:bg-canvas">Open in Google Drive</a>
                @endif
                <button type="button" data-modal-close class="h-9 rounded-md bg-brand px-4 text-[11px] font-semibold text-white hover:bg-blue-700">Close</button>
            </div>
        </x-modal>
    @endforeach
@endsection
