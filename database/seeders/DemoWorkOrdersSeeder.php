<?php

namespace Database\Seeders;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\Technician;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderNote;
use App\Models\WorkOrderUpdate;
use Illuminate\Database\Seeder;

/**
 * Development only. Run DemoDashboardSeeder first (needs reports), then:
 *   php artisan db:seed --class=DemoWorkOrdersSeeder
 * Creates 4 technicians and up to 56 work orders this month:
 * 8 assigned / 6 accepted / 18 in progress / 21 completed / 3 cancelled.
 */
class DemoWorkOrdersSeeder extends Seeder
{
    public function run(): void
    {
        if (WorkOrder::exists()) {
            $this->command?->warn('Work orders already exist - demo seeding skipped.');

            return;
        }

        $admin = User::where('role', 'admin')->first();
        if (! $admin || ! Report::exists()) {
            $this->command?->error('Sign in once as an admin and run DemoDashboardSeeder first.');

            return;
        }

        $techs = collect([
            ['MCC-M001', 'Andrea Flores',    'ICT',        'Computer & ICT equipment repair', 'andrea.flores',    '211'],
            ['MCC-M002', 'Joel Bautista',    'Electrical', 'Electrical systems & lighting',   'joel.bautista',    '212'],
            ['MCC-M003', 'Ramon Dela Cruz',  'Carpentry',  'Carpentry & furniture repair',    'ramon.delacruz',   '214'],
            ['MCC-M004', 'Luis Navarro',     'Plumbing',   'Plumbing & water systems',        'luis.navarro',     '215'],
        ])->map(fn ($t) => Technician::firstOrCreate(['staff_id' => $t[0]], [
            'name' => $t[1], 'specialty' => $t[2], 'specialization' => $t[3],
            'email' => $t[4] . '@demo.invalid', 'extension' => $t[5],
        ]));

        $take = fn (ReportStatus $status, int $n) => Report::where('status', $status->value)->inRandomOrder()->limit($n)->get();

        $verified = $take(ReportStatus::Verified, 17);
        $specs = collect()
            ->merge($take(ReportStatus::Resolved, 21)->map(fn ($r) => ['completed', $r]))
            ->merge($take(ReportStatus::InProgress, 18)->map(fn ($r) => ['in_progress', $r]))
            ->merge($verified->slice(0, 8)->map(fn ($r) => ['assigned', $r]))
            ->merge($verified->slice(8, 6)->map(fn ($r) => ['accepted', $r]))
            ->merge($verified->slice(14, 3)->map(fn ($r) => ['cancelled', $r]));

        $tasks = ['Repair classroom chair', 'Replace ceiling light', 'Replace lab keyboard', 'Replace faucet seal', 'Secure wall outlet', 'Replace door hinge', 'Inspect projector', 'Inspect corridor bench'];

        $monthMinutes = max(120, (int) abs(now()->startOfMonth()->diffInMinutes(now())));
        $rows = $specs->map(fn ($s) => $s + ['created' => now()->startOfMonth()->addMinutes(rand(0, $monthMinutes - 60))])
            ->sortBy(fn ($s) => $s['created']->getTimestamp())->values();

        foreach ($rows as $i => $row) {
            $status  = $row[0];
            $report  = $row[1];
            $created = $row['created'];
            $tech      = $techs->random();
            $accepted  = in_array($status, ['accepted', 'in_progress', 'completed'], true) ? $created->copy()->addHour()->min(now()) : null;
            $completed = $status === 'completed' ? $created->copy()->addDays(rand(1, 2))->addHours(rand(0, 6))->min(now()) : null;

            $order = WorkOrder::create([
                'reference_no'  => sprintf('WO-%d-%03d', now()->year, $i + 1),
                'report_id'     => $report->id,
                'technician_id' => $tech->id,
                'created_by'    => $admin->id,
                'title'         => $tasks[array_rand($tasks)],
                'instructions'  => 'Inspect the reported issue, repair or replace the damaged parts, and test before returning the item to use.',
                'priority'      => ['low', 'medium', 'high'][rand(0, 2)],
                'status'        => $status,
                'progress'      => match ($status) { 'completed' => 100, 'in_progress' => rand(20, 80), default => 0 },
                'target_at'     => $created->copy()->addDays(rand(2, 4))->setTime(17, 0),
                'accepted_at'   => $accepted,
                'completed_at'  => $completed,
                'reviewed_by'   => $completed ? $admin->id : null,
                'reviewed_at'   => $completed?->copy()->addMinutes(15)->min(now()),
                'evidence_name' => $completed && rand(0, 1) ? 'repair_' . ($i + 1) . '.jpg' : null,
                'evidence_url'  => null,
                'created_at'    => $created,
                'updated_at'    => $completed ?? $accepted ?? $created,
            ]);
            if ($order->evidence_name) {
                $order->update(['evidence_url' => 'https://drive.google.com/file/d/demo-' . ($i + 1) . '/view']);
            }

            $log = fn (string $st, string $note, $at, $user = null) => WorkOrderUpdate::create([
                'work_order_id' => $order->id, 'user_id' => $user, 'status' => $st, 'note' => $note,
                'created_at' => $at, 'updated_at' => $at,
            ]);

            $log('assigned', 'Assigned to ' . $tech->name . '.', $created, $admin->id);
            if ($accepted)  $log('accepted', explode(' ', $tech->name)[0] . ' accepted the assignment and scheduled an inspection.', $accepted);
            if ($status === 'in_progress') $log('in_progress', 'Item isolated. Replacement parts prepared.', $accepted->copy()->addHours(3)->min(now()));
            if ($completed) $log('completed', 'Repair finished and checked. Item returned to use.', $completed);
            if ($status === 'cancelled') $log('cancelled', 'Work order cancelled by the administrator.', $created->copy()->addHours(5)->min(now()), $admin->id);

            if ($completed && rand(0, 1)) {
                WorkOrderNote::create(['work_order_id' => $order->id, 'user_id' => $admin->id, 'body' => 'Replacement parts used and item returned after the safety check.', 'created_at' => $completed, 'updated_at' => $completed]);
            }

            // Keep the linked report consistent with the work order
            if (in_array($status, ['assigned', 'accepted'], true)) {
                $report->update(['status' => ReportStatus::InProgress]);
            }
        }
    }
}
