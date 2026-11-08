<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\Location;
use App\Models\Report;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The real MCC buildings and rooms (from the campus location list).
 *   php artisan db:seed --class=RealCampusSeeder
 * Safe to run more than once. If the old demo buildings (Academic Bldg, Science Bldg, ICT Bldg, Admin Bldg,
 * University Library, Engineering Annex) still exist, their reports are moved to real rooms and the demo buildings are removed.
 */
class RealCampusSeeder extends Seeder
{
    /** [code, name, type, note] per room; code is null when the list gives no room number. */
    private function campus(): array
    {
        $classroom = fn (string $code) => [$code, 'Classroom', 'Classroom', null];

        return [
            'Building 1' => [
                '1st Floor' => [
                    ['MB101', 'MIS Office', 'Office', null],
                    $classroom('MB102'), $classroom('MB103'), $classroom('MB104'), $classroom('MB105'), $classroom('MB106'), $classroom('MB107'),
                ],
                '2nd Floor' => [
                    $classroom('MB201'),
                    ['MB202', 'CCTV Room', 'Office', null],
                    ['MB203', 'CCTV Room', 'Office', null],
                    ['MB204', 'Supreme Student Council Office', 'Classroom', 'Listed as a classroom in the campus list'],
                    $classroom('MB205'), $classroom('MB206'), $classroom('MB207'), $classroom('MB208'), $classroom('MB210'),
                ],
            ],
            'Building 2' => [
                '1st Floor' => [
                    ['MB110', 'Clinic', 'Facility', 'Facility / Clinic'],
                    ['MB111', 'Mac Laboratory', 'Classroom', 'Type taken from the map label'],
                    ['MB112 & MB113', 'Cisco Laboratory', 'Laboratory', null],
                    ['MB114', 'Research Office', 'Office', null],
                ],
                '2nd Floor' => [
                    [null, 'Library', 'Facility', 'Room number not specified'],
                    [null, 'Office of the Discipline and Services', 'Office', 'Room number not specified'],
                    [null, 'Office of the Student Affairs and Services', 'Office', 'Room number not specified'],
                ],
            ],
            'Building 3' => [
                '1st Floor' => [
                    ['MB117', 'Guidance Office', 'Office', null],
                    ['MB118', 'Board Room', 'Laboratory', 'Type taken from the map label'],
                    ['MB119', 'Accreditation Center / Records Office', 'Office', null],
                    ['MB120', 'Planning and Legal Office', 'Office', null],
                ],
                '2nd Floor' => [
                    [null, 'Cultural Hall', 'Facility', 'Room number not specified'],
                    ['MB214', 'iLab', 'Laboratory', null],
                    ['MB215', 'Advanced Laboratory', 'Laboratory', null],
                    ['MB216', 'Basic Laboratory', 'Laboratory', null],
                    ['MB217', 'MIS Support Service Office', 'Office', null],
                    $classroom('MB218'), $classroom('MB219'),
                    ['MB220', 'Vacant Office', 'Office', null],
                ],
            ],
            'Building 3 Annex' => [
                '1st Floor' => [
                    [null, 'Front Desk / Front Office', 'Office', null],
                    [null, 'Hometown', 'Facility', null],
                    [null, 'Hot Kitchen', 'Facility', null],
                    [null, 'Hotel', 'Facility', null],
                ],
            ],
            'Female Comfort Room (CR)' => [
                'Outdoor Area / Side Stairs Bldg. 1' => [[null, 'Female Comfort Room', 'Restroom', null]],
            ],
            'Institute Offices' => [
                '1st Floor' => [
                    [null, 'Alumni Office / External Affairs Office', 'Office', null],
                    [null, 'ICS Office', 'Office', null],
                    [null, 'IHTM Faculty Office', 'Office', null],
                ],
            ],
            'Male Comfort Room (CR)' => [
                '1st Floor / Side Stairs Bldg. 3' => [[null, 'Male Comfort Room', 'Restroom', null]],
            ],
            'MCC Gymnasium' => [
                '1st Floor' => [[null, 'Gymnasium', 'Facility', null]],
            ],
            'Office of the Vice President for Academic Affairs (OVPAA)' => [
                '1st Floor' => [['OVPAA', 'Office of the Vice President', 'Office', null]],
            ],
            'Old Building' => [
                '1st Floor' => [
                    [null, 'Sports and Athletics Unit', 'Office', null],
                    [null, 'Supplies Office', 'Office', null],
                    [null, 'Utilities', 'Facility', null],
                ],
            ],
            'Student Center' => [
                '1st Floor' => [
                    [null, 'Cashier', 'Facility', 'Facility / Service counter'],
                    [null, 'IGP Office', 'Office', null],
                    [null, 'Registrar', 'Office', null],
                ],
            ],
        ];
    }

    public function run(): void
    {
        DB::transaction(function () {
            foreach ($this->campus() as $buildingName => $floors) {
                $building = Building::firstOrCreate(['name' => $buildingName]);
                $building->update(['is_active' => true]);

                foreach ($floors as $floor => $rooms) {
                    foreach ($rooms as [$code, $name, $type, $note]) {
                        Location::updateOrCreate(
                            ['building_id' => $building->id, 'code' => $code, 'name' => $name],
                            ['floor' => $floor, 'type' => $type, 'notes' => $note]
                        );
                    }
                }
            }

            $this->retireDemoBuildings();
        });
    }

    /** Moves reports from the old demo rooms to real rooms (same demo room -> same real room), then removes the demo buildings. */
    private function retireDemoBuildings(): void
    {
        $demo = Building::whereIn('name', ['Academic Bldg', 'Science Bldg', 'ICT Bldg', 'Admin Bldg', 'University Library', 'Engineering Annex'])->get();
        if ($demo->isEmpty()) {
            return;
        }

        $realRooms = Location::whereHas('building', fn ($b) => $b->whereNotIn('id', $demo->pluck('id')))->pluck('id');

        foreach (Location::whereIn('building_id', $demo->pluck('id'))->get() as $old) {
            $target = $realRooms->random();

            Report::withTrashed()->where('location_id', $old->id)->update(['location_id' => $target]);
            if (Schema::hasColumn('assets', 'location_id')) {
                DB::table('assets')->where('location_id', $old->id)->update(['location_id' => $target]);
            }

            $old->delete();
        }

        Building::whereIn('id', $demo->pluck('id'))->delete();
    }
}
