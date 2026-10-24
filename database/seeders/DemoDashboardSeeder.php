<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\Category;
use App\Models\Location;
use App\Models\Report;
use App\Models\ReportUpdate;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo data with the same status split as the Figma dashboard (248 reports:
 * 32 pending / 22 verified / 18 in progress / 166 resolved / 10 rejected).
 * Development only: php artisan db:seed --class=DemoDashboardSeeder
 */
class DemoDashboardSeeder extends Seeder
{
    public function run(): void
    {
        if (Report::withTrashed()->exists()) {
            $this->command?->warn('Reports already exist - demo seeding skipped.');

            return;
        }

        $admin = User::where('role', 'admin')->first()
            ?? User::create(['name' => 'Demo Admin', 'email' => 'demo.admin@invalid.local', 'role' => 'admin']);

        // Demo reporters (cannot sign in: not on the MCC domain / tenant)
        $reporters = collect(['Elena Reyes', 'Paolo Garcia', 'Nina Flores', 'Luis Mendoza', 'Carla Lim', 'Ana Cruz', 'Mark Villanueva', 'Bea Ramos', 'Jose Tan', 'Mia Torres'])
            ->map(fn ($name) => User::firstOrCreate(
                ['email' => strtolower(str_replace(' ', '.', $name)) . '@demo.invalid'],
                ['name' => $name, 'role' => 'user']
            ));

        $categories = collect(['Furniture', 'Electrical Facilities', 'Computer Equipment', 'Plumbing', 'Laboratory Equipment'])
            ->map(fn ($n) => Category::firstOrCreate(['name' => $n]));

        $locations = collect([
            'Academic Bldg' => ['ACAD', ['203', '101']],
            'Science Bldg'  => ['SCI',  ['104', 'Lab 1']],
            'ICT Bldg'      => ['ICT',  ['Lab 2', 'Lab 1']],
            'Admin Bldg'    => ['ADM',  ['G/F', '2/F']],
        ])->flatMap(function ($def, $name) {
            $building = Building::firstOrCreate(['code' => $def[0]], ['name' => $name]);

            return collect($def[1])->map(fn ($room) => Location::firstOrCreate(
                ['building_id' => $building->id, 'name' => $room]
            ));
        });

        $titles = [
            'Broken Classroom Chair', 'Defective Ceiling Light', 'Damaged Computer Keyboard',
            'Leaking Water Faucet', 'Broken Laboratory Table', 'Flickering Projector', 'Clogged Drain',
        ];

        $statuses = collect([['pending', 32], ['verified', 22], ['in_progress', 18], ['resolved', 166], ['rejected', 10]])
            ->flatMap(fn ($p) => array_fill(0, $p[1], $p[0]))
            ->shuffle()
            ->map(fn ($status) => [
                'status'  => $status,
                'created' => now()->subDays(rand(0, 44))->subMinutes(rand(0, 1439)),
            ])
            ->sortBy(fn ($r) => $r['created']->getTimestamp())
            ->values();

        foreach ($statuses as $i => $row) {
            $created  = $row['created'];
            $resolved = $row['status'] === 'resolved' ? $created->copy()->addDays(rand(1, 5))->min(now()) : null;

            $report = Report::create([
                'reference_no' => sprintf('AW-%d-%03d', now()->year, $i + 1),
                'reporter_id'  => $reporters->random()->id,
                'category_id'  => $categories->random()->id,
                'location_id'  => $locations->random()->id,
                'title'        => $titles[array_rand($titles)],
                'description'  => 'Demo report generated for dashboard development.',
                'priority'     => ['low', 'medium', 'high'][array_rand([0, 1, 2])],
                'status'       => $row['status'],
                'resolved_at'  => $resolved,
                'created_at'   => $created,
                'updated_at'   => $resolved ?? $created,
            ]);

            ReportUpdate::create([
                'report_id' => $report->id, 'user_id' => $admin->id,
                'from_status' => null, 'to_status' => 'pending',
                'created_at' => $created, 'updated_at' => $created,
            ]);

            if ($row['status'] !== 'pending') {
                $at = $resolved ?? $created->copy()->addHours(rand(1, 48))->min(now());
                ReportUpdate::create([
                    'report_id' => $report->id, 'user_id' => $admin->id,
                    'from_status' => 'pending', 'to_status' => $row['status'],
                    'created_at' => $at, 'updated_at' => $at,
                ]);
            }
        }
    }
}
