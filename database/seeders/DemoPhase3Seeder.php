<?php

namespace Database\Seeders;

use App\Models\Report;
use App\Models\ReportAttachment;
use App\Models\ReportNote;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Development only. Run after DemoDashboardSeeder and DemoWorkOrdersSeeder:
 *   php artisan db:seed --class=DemoPhase3Seeder
 * Adds evidence records, report notes, rejection/resolution text, reporter departments
 * and more personnel so the new pages have something to show. Safe to run twice.
 */
class DemoPhase3Seeder extends Seeder
{
    public function run(): void
    {
        // Personnel: phones, plus a few more people (one unavailable, one inactive)
        $extra = [
            ['MCC-M005', 'Miguel Santos', 'Plumbing',   'Plumbing & water systems',   'miguel.santos', '216', true,  true],
            ['MCC-M006', 'Carla Mendoza', 'HVAC',       'Air conditioning & ventilation', 'carla.mendoza', '217', false, true],
            ['MCC-M007', 'Rico Torres',   'Painting',   'Painting & finishing',        'rico.torres',   '218', true,  false],
        ];
        foreach ($extra as [$staff, $name, $specialty, $specialization, $mail, $ext, $available, $active]) {
            Technician::firstOrCreate(['staff_id' => $staff], [
                'name' => $name, 'specialty' => $specialty, 'specialization' => $specialization,
                'email' => $mail . '@demo.invalid', 'extension' => $ext, 'is_available' => $available, 'is_active' => $active,
            ]);
        }
        Technician::whereNull('phone')->get()->each(fn ($t) => $t->update(['phone' => '0917 000 ' . str_pad((string) $t->id, 4, '0', STR_PAD_LEFT)]));

        // Reporter departments (demo reporters only)
        $departments = ['Faculty', 'Student', 'Staff'];
        User::where('email', 'like', '%@demo.invalid')->whereNull('department')->get()
            ->each(fn ($u, $i) => $u->update(['department' => $departments[$i % 3]]));

        $admin = User::where('role', 'admin')->first();

        // Evidence metadata: the original files live in Google Drive, so there is no inline preview in the demo
        if (! ReportAttachment::exists()) {
            Report::orderBy('id')->limit(120)->get()->each(function (Report $r) {
                ReportAttachment::create([
                    'report_id'  => $r->id,
                    'name'       => 'evidence_' . $r->id . '.jpg',
                    'mime_type'  => 'image/jpeg',
                    'size_bytes' => rand(900_000, 3_800_000),
                    'drive_url'  => 'https://drive.google.com/file/d/demo-' . $r->id . '/view',
                ]);
            });
        }

        // Rejected reports get a reason, resolved reports get a closing note
        Report::where('status', 'rejected')->whereNull('rejection_reason')->get()->each(fn (Report $r) => $r->update([
            'rejection_reason'      => 'Duplicate of an existing report',
            'rejection_explanation' => 'This issue was already reported and is being handled under another report.',
            'rejected_by'           => $admin?->id,
            'rejected_at'           => $r->updated_at,
        ]));

        Report::where('status', 'resolved')->whereNull('resolution_notes')->get()->each(fn (Report $r) => $r->update([
            'resolution_notes' => 'Repair completed and tested. The item was returned to normal use.',
            'resolved_by'      => $admin?->id,
        ]));

        if ($admin && ! ReportNote::exists()) {
            Report::inRandomOrder()->limit(15)->get()->each(fn (Report $r) => ReportNote::create([
                'report_id' => $r->id, 'user_id' => $admin->id,
                'body'      => 'Checked with the department head. Please prioritise before the next class schedule.',
            ]));
        }
    }
}
