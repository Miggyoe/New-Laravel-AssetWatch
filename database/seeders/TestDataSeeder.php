<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Location;
use App\Models\Report;
use App\Models\ReportAttachment;
use App\Models\ReportNote;
use App\Models\ReportUpdate;
use App\Models\Technician;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderNote;
use App\Models\WorkOrderUpdate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * A small, consistent set of test data (development only):
 *   php artisan db:seed --class=TestDataSeeder
 *
 * - the 7 asset categories from the design
 * - 3 reporters and 5 maintenance technicians (one marked unavailable)
 * - 8 incident reports: 3 pending, 1 verified, 2 in progress, 1 resolved, 1 rejected
 * - 3 work orders (assigned, in progress, completed), report notes, evidence records, and activity log entries
 * It uses your real campus rooms and your own administrator account, and it never touches existing data:
 * it stops if any report already exists. Demo accounts use @demo.invalid addresses and cannot sign in.
 */
class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        if (Report::withTrashed()->exists()) {
            $this->command?->warn('Reports already exist - test data skipped.');

            return;
        }

        $admin = User::where('role', 'admin')->first();
        if (! $admin) {
            $this->command?->error('Sign in once as an administrator first (the test data is recorded under your account).');

            return;
        }

        if (! Location::exists()) {
            $this->command?->error('There are no campus rooms yet. Run: php artisan db:seed --class=RealCampusSeeder');

            return;
        }

        DB::transaction(fn () => $this->seed($admin));

        $this->command?->info('Test data created: 8 reports, 3 work orders, 5 technicians.');
    }

    private function seed(User $admin): void
    {
        // ---------------------------------------------------------------- categories
        $categories = collect([
            'Furniture'             => 'Chairs, desks, cabinets, and seating.',
            'Electrical Facilities' => 'Lighting, outlets, switches, and wiring.',
            'Computer Equipment'    => 'Computers, peripherals, and projectors.',
            'Laboratory Equipment'  => 'Lab tables, fixtures, and teaching equipment.',
            'Plumbing'              => 'Faucets, drains, pipes, and restroom fixtures.',
            'Classroom Facilities'  => 'Doors, windows, boards, and classroom fittings.',
            'Other Campus Assets'   => 'Campus assets not covered by other categories.',
        ])->mapWithKeys(fn ($description, $name) => [$name => Category::firstOrCreate(['name' => $name], ['description' => $description])]);

        // ---------------------------------------------------------------- people
        $reporters = collect([
            ['Elena Reyes', 'Faculty'], ['Paolo Garcia', 'Student'], ['Nina Flores', 'Staff'],
        ])->map(fn ($p) => $this->user($p[0], 'user', Str::slug($p[0], '.') . '@demo.invalid', $p[1]));

        $nextStaff = (int) Technician::all()->map(fn ($t) => (int) preg_replace('/\D/', '', $t->staff_id))->max();
        $techs = collect([
            ['Andrea Flores',   'ICT',        'Computer & ICT equipment repair',    true],
            ['Joel Bautista',   'Electrical', 'Electrical systems & lighting',      true],
            ['Ramon Dela Cruz', 'Carpentry',  'Carpentry & furniture repair',       true],
            ['Luis Navarro',    'Plumbing',   'Plumbing & water systems',           true],
            ['Carla Mendoza',   'HVAC',       'Air conditioning & ventilation',     false],
        ])->mapWithKeys(function ($t, $i) use (&$nextStaff) {
            $email = Str::slug($t[0], '.') . '@demo.invalid';
            $tech  = Technician::where('email', $email)->first();

            if (! $tech) {
                $account = $this->user($t[0], 'technician', $email, null);
                $tech = Technician::create([
                    'user_id' => $account->id, 'staff_id' => sprintf('MCC-M%03d', ++$nextStaff), 'name' => $t[0],
                    'specialty' => $t[1], 'specialization' => $t[2], 'email' => $email,
                    'phone' => '0917 000 ' . str_pad((string) (100 + $i), 4, '0', STR_PAD_LEFT),
                    'is_active' => true, 'is_available' => $t[3],
                ]);
            }

            return [$t[1] => $tech];
        });

        // ---------------------------------------------------------------- helpers
        $room = fn (string $needle) => Location::where('code', $needle)->orWhere('name', $needle)->first() ?? Location::inRandomOrder()->first();
        $n = (int) Report::withTrashed()->whereYear('created_at', now()->year)->count();

        $ru = fn (Report $r, ?string $from, string $to, ?User $by, $at, ?string $remarks = null) => ReportUpdate::create([
            'report_id' => $r->id, 'user_id' => $by?->id, 'from_status' => $from, 'to_status' => $to, 'remarks' => $remarks,
            'created_at' => $at, 'updated_at' => $at,
        ]);

        $wu = fn (WorkOrder $o, string $status, string $note, ?User $by, $at) => WorkOrderUpdate::create([
            'work_order_id' => $o->id, 'user_id' => $by?->id, 'status' => $status, 'note' => $note,
            'created_at' => $at, 'updated_at' => $at,
        ]);

        $log = fn (string $action, string $description, Model $subject, ?User $by, ?string $from, ?string $to, $at) => ActivityLog::create([
            'user_id' => $by?->id, 'action' => $action, 'description' => $description,
            'subject_type' => $subject->getMorphClass(), 'subject_id' => $subject->getKey(),
            'from_status' => $from, 'to_status' => $to, 'ip_address' => '127.0.0.1',
            'created_at' => $at, 'updated_at' => $at,
        ]);

        // ---------------------------------------------------------------- reports
        $specs = [
            ['Broken Classroom Chair',     'A student chair in the room has a cracked seat and is unsafe to use.',        'Furniture',             'MB102',              'medium', 'pending',     now()->subHours(2)],
            ['Defective Ceiling Light',    'Two ceiling lights flicker and then switch off during class hours.',          'Electrical Facilities', 'MB205',              'high',   'pending',     now()->subHours(5)],
            ['Leaking Water Faucet',       'The faucet at the first sink keeps dripping after it is closed.',             'Plumbing',              'Male Comfort Room',  'medium', 'pending',     now()->subDay()],
            ['Damaged Computer Keyboard',  'Several keys on a workstation keyboard no longer respond.',                   'Computer Equipment',    'MB214',              'medium', 'verified',    now()->subDays(2)],
            ['Flickering Projector',       'The ceiling projector shuts down after about ten minutes of use.',            'Computer Equipment',    'MB107',              'medium', 'in_progress', now()->subDays(4)],
            ['Clogged Drain',              'The floor drain is clogged and water collects after cleaning.',               'Plumbing',              'Female Comfort Room','high',   'in_progress', now()->subDays(3)],
            ['Loose Classroom Door',       'The door hinge is loose and the door scrapes the floor when opened.',         'Classroom Facilities',  'MB218',              'low',    'resolved',    now()->subDays(8)],
            ['Broken Laboratory Table',    'A laboratory table leg is damaged. (Same table as an earlier report.)',       'Laboratory Equipment',  'MB215',              'medium', 'rejected',    now()->subDays(6)],
        ];

        $reports = [];
        foreach ($specs as $i => [$title, $description, $category, $where, $priority, $status, $at]) {
            $reports[$title] = Report::create([
                'reference_no' => sprintf('AW-%d-%03d', now()->year, ++$n),
                'reporter_id'  => $reporters[$i % $reporters->count()]->id,
                'category_id'  => $categories[$category]->id,
                'location_id'  => $room($where)->id,
                'title'        => $title,
                'description'  => $description,
                'priority'     => $priority,
                'status'       => $status,
                'created_at'   => $at,
                'updated_at'   => $at,
            ]);

            $r = $reports[$title];
            $ru($r, null, 'pending', $r->reporter, $at, 'Report submitted.');

            ReportAttachment::create([
                'report_id' => $r->id, 'path' => '', 'name' => Str::slug($title, '_') . '.jpg', 'mime_type' => 'image/jpeg', // no inline preview: only the Drive link
                'size_bytes' => rand(900_000, 3_400_000), 'drive_url' => 'https://drive.google.com/file/d/demo-' . $r->id . '/view',
                'created_at' => $at, 'updated_at' => $at,
            ]);
        }

        // ---------------------------------------------------------------- workflow
        $verify = function (Report $r, $at) use ($admin, $ru, $log) {
            $r->update(['verified_by' => $admin->id, 'verified_at' => $at]);
            $ru($r, 'pending', 'verified', $admin, $at, 'Verified by administrator.');
            $log('report.verified', "Verified report {$r->reference_no}", $r, $admin, 'pending', 'verified', $at);
        };

        $workOrder = function (Report $r, Technician $tech, string $title, string $instructions, string $priority, $at, $targetInDays) use ($admin, $wu, $log): WorkOrder {
            $count = WorkOrder::count() + 1;
            $order = WorkOrder::create([
                'reference_no' => sprintf('WO-%d-%03d', now()->year, $count),
                'report_id' => $r->id, 'technician_id' => $tech->id, 'created_by' => $admin->id,
                'title' => $title, 'instructions' => $instructions, 'priority' => $priority, 'status' => 'assigned',
                'target_at' => now()->addDays($targetInDays)->setTime(17, 0), 'created_at' => $at, 'updated_at' => $at,
            ]);
            $wu($order, 'assigned', 'Assigned to ' . $tech->name . '.', $admin, $at);
            $log('work_order.created', "Created work order {$order->reference_no} for {$r->reference_no}", $order, $admin, null, 'assigned', $at);

            return $order;
        };

        // 4. verified, waiting for a work order (use it to test "Create Work Order")
        $verify($reports['Damaged Computer Keyboard'], now()->subDays(2)->addHour());

        // 5. in progress: work order accepted and started
        $r = $reports['Flickering Projector'];
        $verify($r, now()->subDays(4)->addHour());
        $order = $workOrder($r, $techs['ICT'], 'Inspect and repair the projector', 'Check the lamp, vents and power supply; replace parts if needed and test for 30 minutes.', 'medium', now()->subDays(4)->addHours(3), 2);
        $accepted = now()->subDays(3)->addHours(20);
        $order->update(['status' => 'in_progress', 'progress' => 40, 'accepted_at' => $accepted, 'updated_at' => now()->subDays(3)]);
        $wu($order, 'accepted', 'Accepted. Inspection scheduled for tomorrow morning.', $techs['ICT']->user, $accepted);
        $log('work_order.accepted', "Accepted work order {$order->reference_no}", $order, $techs['ICT']->user, 'assigned', 'accepted', $accepted);
        $wu($order, 'in_progress', 'Vents cleaned. Waiting for a replacement fan.', $techs['ICT']->user, now()->subDays(3));
        $log('work_order.started', "Started repair for {$order->reference_no}", $order, $techs['ICT']->user, 'accepted', 'in_progress', now()->subDays(3));
        $r->update(['updated_at' => now()->subDays(3)]);
        $ru($r, 'verified', 'in_progress', $admin, now()->subDays(4)->addHours(3), "Work order {$order->reference_no} created.");

        // 6. in progress: work order only assigned so far
        $r = $reports['Clogged Drain'];
        $verify($r, now()->subDays(3)->addHour());
        $order = $workOrder($r, $techs['Plumbing'], 'Clear the clogged floor drain', 'Clear the blockage, flush the line and check that water drains freely.', 'high', now()->subDays(3)->addHours(2), 1);
        $ru($r, 'verified', 'in_progress', $admin, now()->subDays(3)->addHours(2), "Work order {$order->reference_no} created.");
        ReportNote::create(['report_id' => $r->id, 'user_id' => $admin->id, 'body' => 'Restroom is in daily use. Please schedule outside class hours.', 'created_at' => now()->subDays(2), 'updated_at' => now()->subDays(2)]);

        // 7. resolved: full cycle, reviewed by the administrator
        $r = $reports['Loose Classroom Door'];
        $verify($r, now()->subDays(8)->addHour());
        $order = $workOrder($r, $techs['Carpentry'], 'Tighten the hinges and re-hang the door', 'Tighten or replace the hinge screws, plane the edge if it still scrapes.', 'low', now()->subDays(7)->addHours(2), -4);
        $tech = $techs['Carpentry'];
        $t1 = now()->subDays(7)->addHours(5);
        $t2 = now()->subDays(6)->addHours(2);
        $t3 = now()->subDays(5)->addHours(4);
        $t4 = now()->subDays(5)->addHours(6);
        $wu($order, 'accepted', 'Accepted the assignment.', $tech->user, $t1);
        $log('work_order.accepted', "Accepted work order {$order->reference_no}", $order, $tech->user, 'assigned', 'accepted', $t1);
        $wu($order, 'in_progress', 'Hinge screws replaced; door re-hung.', $tech->user, $t2);
        $log('work_order.started', "Started repair for {$order->reference_no}", $order, $tech->user, 'accepted', 'in_progress', $t2);
        $wu($order, 'completed', 'Door opens and closes freely. Checked twice.', $tech->user, $t3);
        $log('work_order.completed', "Completed repair for {$order->reference_no}", $order, $tech->user, 'in_progress', 'completed', $t3);
        $order->update([
            'status' => 'completed', 'progress' => 100, 'accepted_at' => $t1, 'completed_at' => $t3,
            'reviewed_by' => $admin->id, 'reviewed_at' => $t4, 'evidence_name' => 'door_fixed.jpg',
            'evidence_url' => 'https://drive.google.com/file/d/demo-door-fixed/view', 'updated_at' => $t4,
        ]);
        $ru($r, 'verified', 'in_progress', $admin, now()->subDays(7)->addHours(2), "Work order {$order->reference_no} created.");
        $ru($r, 'in_progress', 'resolved', $admin, $t4, 'Repair completed and checked. The door is back in use.');
        $r->update(['resolved_at' => $t4, 'resolved_by' => $admin->id, 'resolution_notes' => 'Repair completed and checked. The door is back in use.', 'updated_at' => $t4]);
        $log('report.status_changed', "Changed {$r->reference_no} from In Progress to Resolved", $r, $admin, 'in_progress', 'resolved', $t4);
        WorkOrderNote::create(['work_order_id' => $order->id, 'user_id' => $admin->id, 'body' => 'Replacement screws were taken from the maintenance stock.', 'created_at' => $t4, 'updated_at' => $t4]);

        // 8. rejected as a duplicate
        $r = $reports['Broken Laboratory Table'];
        $at = now()->subDays(6)->addHours(2);
        $r->update([
            'rejected_by' => $admin->id, 'rejected_at' => $at, 'rejection_reason' => 'Duplicate of an existing report',
            'rejection_explanation' => 'This table was already reported and is waiting for repair under another report.', 'updated_at' => $at,
        ]);
        $ru($r, 'pending', 'rejected', $admin, $at, 'Duplicate of an existing report: already reported.');
        $log('report.rejected', "Rejected report {$r->reference_no} (Duplicate of an existing report)", $r, $admin, 'pending', 'rejected', $at);

        // a note on the urgent pending report
        ReportNote::create(['report_id' => $reports['Defective Ceiling Light']->id, 'user_id' => $admin->id, 'body' => 'High priority: classes are held in this room every afternoon.', 'created_at' => now()->subHours(4), 'updated_at' => now()->subHours(4)]);
    }

    /** Demo account: random password and an @demo.invalid address, so it can never sign in. */
    private function user(string $name, string $role, string $email, ?string $department): User
    {
        return User::where('email', $email)->first() ?? User::forceCreate([
            'name' => $name, 'email' => $email, 'role' => $role, 'department' => $department,
            'password' => Hash::make(Str::random(40)),
        ]);
    }
}
