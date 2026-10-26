@extends('layouts.admin')

@section('title', $order->reference_no)
@section('breadcrumb', 'Work Orders')

@section('content')
    @php
        $report      = $order->report;
        $reportUrl   = Route::has('admin.reports.show') ? route('admin.reports.show', $report) : null;
        $profileUrl  = Route::has('admin.personnel.show') ? route('admin.personnel.show', $order->technician) : '#';
        $isCompleted = $order->status === \App\Enums\WorkOrderStatus::Completed;
        $canReassign = $order->status !== \App\Enums\WorkOrderStatus::Cancelled;

        $evidenceHost  = $order->evidence_url ? parse_url($order->evidence_url, PHP_URL_HOST) : null;
        $evidenceOk    = $order->evidence_url && str_starts_with($order->evidence_url, 'https://');
        $evidenceSource = $evidenceHost && str_contains($evidenceHost, 'drive.google.com') ? 'Google Drive' : ($evidenceHost ?: 'Link');
    @endphp

    {{-- Page heading --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-col gap-[5px] leading-[1.45]">
            <h1 class="text-[26px] font-semibold">{{ $order->title }}</h1>
            <p class="text-xs text-muted">
                {{ $order->reference_no }} · Linked incident
                @if ($reportUrl) <a href="{{ $reportUrl }}" class="font-medium text-brand hover:underline">{{ $report->reference_no }}</a>
                @else {{ $report->reference_no }} @endif
            </p>
        </div>
        <div class="flex items-center gap-2.5">
            <p class="text-[11px] text-muted">Authorized administrator action</p>
            @if ($canReassign)
                <button type="button" data-modal-open="reassign-technician"
                        class="rounded-md bg-brand px-4 py-2.5 text-xs font-medium text-white hover:bg-blue-700">Reassign Technician</button>
            @else
                <button type="button" disabled title="Cancelled work orders cannot be reassigned"
                        class="cursor-not-allowed rounded-md bg-brand/50 px-4 py-2.5 text-xs font-medium text-white">Reassign Technician</button>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-[22px] xl:grid-cols-[736fr_402fr]">
        {{-- Left: task + repair updates --}}
        <div class="flex min-w-0 flex-col gap-[18px]">
            <section class="flex flex-col gap-3.5 rounded-[10px] border border-line bg-white p-5 shadow-[0_2px_3px_rgba(23,54,93,0.02)]">
                <h2 class="text-[15px] font-semibold leading-[1.45]">Work order details</h2>

                <div class="flex gap-2">
                    <x-status-badge :status="$order->status" />
                    <x-priority-badge :priority="$order->priority" />
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="leading-[1.6]">
                        <p class="text-[10px] text-muted">Related report</p>
                        <p class="text-xs font-medium">{{ $report->reference_no }} · {{ $report->title }}</p>
                    </div>
                    <div class="leading-[1.6]">
                        <p class="text-[10px] text-muted">Location</p>
                        <p class="text-xs font-medium">{{ $report->placeLabel() }}</p>
                    </div>
                </div>

                <div class="leading-[1.45]">
                    <p class="text-xs font-semibold">Repair instructions</p>
                    <p class="mt-3.5 whitespace-pre-line text-xs">{{ $order->instructions }}</p>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="leading-[1.6]">
                        <p class="text-[10px] text-muted">Assigned by / Date</p>
                        <p class="text-xs font-medium">{{ $order->creator?->name ?? 'Administrator' }} · {{ $order->created_at->format('j M Y, g:i A') }}</p>
                    </div>
                    <div class="leading-[1.6]">
                        <p class="text-[10px] text-muted">Target completion</p>
                        <p class="text-xs font-medium">{{ $order->target_at->format('j M Y · g:i A') }}</p>
                    </div>
                </div>

                @if ($isCompleted)
                    <div class="flex items-start gap-2.5 rounded-md bg-[#e8f7f3] p-3 text-[#0f9d8a]">
                        <x-icon name="circle-check" :size="18" class="mt-px" />
                        <div class="leading-[1.5]">
                            <p class="text-xs font-semibold">
                                {{ $order->progress }}% complete · {{ $order->reviewed_at ? 'Reviewed by administrator' : 'Awaiting administrator review' }}
                            </p>
                            <p class="text-[11px]">
                                Completed {{ $order->completed_at?->format('j M Y, g:i A') }}.
                                @if ($report->status === \App\Enums\ReportStatus::Resolved) Original incident marked Resolved. @endif
                            </p>
                        </div>
                    </div>
                @endif
            </section>

            <section class="flex flex-col gap-3.5 rounded-[10px] border border-line bg-white p-5 shadow-[0_2px_3px_rgba(23,54,93,0.02)]">
                <h2 class="text-[15px] font-semibold leading-[1.45]">Repair updates &amp; completion</h2>

                <div class="flex flex-col gap-[18px]">
                    @forelse ($order->updates as $update)
                        <div class="flex gap-4">
                            <div class="flex w-[130px] shrink-0 flex-col items-start gap-1.5">
                                <p class="whitespace-nowrap text-[10px] leading-[1.45] text-muted">{{ $update->created_at->format('j M · g:i A') }}</p>
                                @if ($statusEnum = $update->statusEnum())
                                    <x-status-badge :status="$statusEnum" />
                                @endif
                            </div>
                            <p class="min-w-0 flex-1 text-xs leading-[1.45]">{{ $update->note }}</p>
                        </div>
                    @empty
                        <p class="text-xs text-muted">No repair updates yet.</p>
                    @endforelse
                </div>

                @if ($evidenceOk)
                    <a href="{{ $order->evidence_url }}" target="_blank" rel="noopener noreferrer"
                       class="text-[11px] font-medium text-brand hover:underline">
                        {{ $order->evidence_name ? $order->evidence_name . ' · ' : '' }}Completion evidence · {{ $evidenceSource }} ↗
                    </a>
                @endif
            </section>
        </div>

        {{-- Right: technician + notes --}}
        <div class="flex min-w-0 flex-col gap-[18px]">
            <section class="flex flex-col gap-3.5 rounded-[10px] border border-line bg-white p-5 shadow-[0_2px_3px_rgba(23,54,93,0.02)]">
                <h2 class="text-[15px] font-semibold leading-[1.45]">Assigned technician</h2>

                <div class="leading-[1.6]">
                    <p class="text-[10px] text-muted">Staff ID / Name</p>
                    <p class="text-xs font-medium">{{ $order->technician->staff_id }} · {{ $order->technician->name }}</p>
                </div>
                <div class="leading-[1.6]">
                    <p class="text-[10px] text-muted">Specialization</p>
                    <p class="text-xs font-medium">{{ $order->technician->specialization ?: $order->technician->specialty }}</p>
                </div>
                <div class="leading-[1.6]">
                    <p class="text-[10px] text-muted">Contact</p>
                    <p class="text-xs font-medium">
                        {{ $order->technician->email ?: '—' }}@if ($order->technician->extension) · Ext. {{ $order->technician->extension }}@endif
                    </p>
                </div>

                <a href="{{ $profileUrl }}" class="text-[11px] font-medium text-brand hover:underline">View personnel profile →</a>
            </section>

            <section class="flex flex-col gap-3.5 rounded-[10px] border border-line bg-white p-5 shadow-[0_2px_3px_rgba(23,54,93,0.02)]">
                <h2 class="text-[15px] font-semibold leading-[1.45]">Internal notes</h2>

                @forelse ($order->notes as $note)
                    <div class="leading-[1.45]">
                        <p class="whitespace-pre-line text-xs">{{ $note->body }}</p>
                        <p class="mt-1 text-[10px] text-muted">{{ $note->user?->name ?? 'Administrator' }} · {{ $note->created_at->format('j M, g:i A') }}</p>
                    </div>
                @empty
                    <p class="text-xs text-muted">No internal notes yet.</p>
                @endforelse

                <form method="POST" action="{{ route('admin.work-orders.notes.store', $order) }}" class="flex flex-col gap-3.5">
                    @csrf
                    <x-textarea name="body" label="Add administrative note" rows="3" maxlength="1000"
                                placeholder="Document a follow-up or review note..." />
                    <button type="submit"
                            class="h-9 w-full rounded-md border border-line bg-white text-[11px] font-semibold text-ink hover:bg-canvas">Save Note</button>
                </form>
            </section>
        </div>
    </div>

    {{-- Reassign technician modal --}}
    @if ($canReassign)
        <dialog id="reassign-technician" data-modal @if ($errors->has('technician_id')) data-autoopen @endif
                class="m-auto w-[480px] max-w-[calc(100vw-2rem)] rounded-xl bg-white p-0 text-ink shadow-[0_18px_60px_rgba(11,27,51,0.15)] backdrop:bg-[rgba(11,27,51,0.6)]">
            <form method="POST" action="{{ route('admin.work-orders.reassign', $order) }}" class="flex flex-col gap-5 p-7">
                @csrf
                @method('PATCH')

                <div class="flex items-start justify-between gap-4">
                    <div class="flex flex-col gap-[5px] leading-[1.45]">
                        <h2 class="text-xl font-semibold">Reassign Technician</h2>
                        <p class="text-xs text-muted">Currently assigned to {{ $order->technician->name }}.</p>
                    </div>
                    <button type="button" data-modal-close class="text-muted hover:text-ink" aria-label="Close"><x-icon name="x" :size="20" /></button>
                </div>

                <x-select name="technician_id" label="New technician" required :submit="false" width="w-full"
                          :options="$assignee" :selected="old('technician_id')" />

                @if ($order->status->isOpen())
                    <div class="flex items-start gap-2.5 rounded-md bg-[#eff6ff] p-3 text-[#2563eb]">
                        <x-icon name="info" :size="17" class="mt-px" />
                        <p class="text-[11px] leading-normal">The new technician will need to accept this work order again. The change is recorded in the activity log.</p>
                    </div>
                @endif

                <div class="flex justify-end gap-2.5">
                    <button type="button" data-modal-close
                            class="h-9 rounded-md border border-line bg-white px-4 text-[11px] font-semibold text-ink hover:bg-canvas">Cancel</button>
                    <button type="submit" class="h-9 rounded-md bg-brand px-4 text-[11px] font-semibold text-white hover:bg-blue-700">Reassign</button>
                </div>
            </form>
        </dialog>
    @endif
@endsection
