<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Report;
use App\Models\ReportUpdate;
use App\Models\Technician;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderUpdate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Development only: turns the existing report and work order history into activity log entries, so the
 * Activity Logs page has data. Run after the other demo seeders:
 *   php artisan db:seed --class=DemoActivityLogSeeder
 * Also gives each demo technician a user account (role "technician", cannot sign in) so the Role column shows it.
 */
class DemoActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        if (ActivityLog::count() >= 50) {
            $this->command?->warn('Activity log already has entries - demo seeding skipped.');

            return;
        }

        // Technician accounts (demo only: not on the MCC domain, random password)
        Technician::whereNull('user_id')->get()->each(function (Technician $t) {
            $user = User::where('email', Str::lower($t->staff_id) . '@demo.invalid')->first()
                ?? User::forceCreate([
                    'name'     => $t->name,
                    'email'    => Str::lower($t->staff_id) . '@demo.invalid',
                    'role'     => 'technician',
                    'password' => Hash::make(Str::random(40)),
                ]);
            $t->update(['user_id' => $user->id]);
        });

        $reportClass = (new Report())->getMorphClass();
        $orderClass  = (new WorkOrder())->getMorphClass();
        $rows        = [];

        $add = function (string $action, string $description, $subjectType, int $subjectId, ?int $userId, ?string $from, ?string $to, $at) use (&$rows) {
            $rows[] = [
                'user_id' => $userId, 'action' => $action, 'description' => $description,
                'subject_type' => $subjectType, 'subject_id' => $subjectId,
                'from_status' => $from, 'to_status' => $to, 'ip_address' => '127.0.0.1',
                'created_at' => $at, 'updated_at' => $at,
            ];
        };

        ReportUpdate::with('report')->get()->each(function (ReportUpdate $u) use ($add, $reportClass) {
            if (! $u->report || ! $u->to_status || $u->to_status === 'pending') {
                return; // the reporter's own submission is not an administrative action
            }

            $ref = $u->report->reference_no;
            [$action, $text] = match ($u->to_status) {
                'verified' => ['report.verified', "Verified report {$ref}"],
                'rejected' => ['report.rejected', "Rejected report {$ref}"],
                default    => ['report.status_changed', "Changed {$ref} from " . Str::headline((string) $u->from_status) . ' to ' . Str::headline($u->to_status)],
            };

            $add($action, $text, $reportClass, $u->report_id, $u->user_id, $u->from_status, $u->to_status, $u->created_at);
        });

        WorkOrderUpdate::with('workOrder.technician')->get()->each(function (WorkOrderUpdate $u) use ($add, $orderClass) {
            $order = $u->workOrder;
            if (! $order || ! $u->status) {
                return;
            }

            $techUser = $order->technician?->user_id;
            $ref      = $order->reference_no;

            [$action, $text, $from, $user] = match ($u->status) {
                'assigned'    => ['work_order.created', "Created work order {$ref}", null, $u->user_id],
                'accepted'    => ['work_order.accepted', "Accepted work order {$ref}", 'assigned', $techUser],
                'in_progress' => ['work_order.started', "Started repair for {$ref}", 'accepted', $techUser],
                'completed'   => ['work_order.completed', "Completed repair for {$ref}", 'in_progress', $techUser],
                default       => ['work_order.cancelled', "Cancelled work order {$ref}", 'assigned', $u->user_id],
            };

            $add($action, $text, $orderClass, $order->id, $user, $from, $u->status, $u->created_at);
        });

        collect($rows)->chunk(200)->each(fn ($chunk) => DB::table('activity_logs')->insert($chunk->all()));

        $this->command?->info(count($rows) . ' activity log entries created.');
    }
}
