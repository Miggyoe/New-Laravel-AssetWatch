<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\Location;
use Illuminate\Database\Seeder;

/**
 * Development only: brings the buildings to the six in the design (112 rooms in total).
 *   php artisan db:seed --class=DemoLocationsSeeder
 * Existing rooms are never removed or renamed. Safe to run twice.
 */
class DemoLocationsSeeder extends Seeder
{
    public function run(): void
    {
        // code => [name used when the building does not exist yet, target number of rooms, active]
        $plan = [
            'ACAD' => ['Academic Bldg',       32, true],
            'SCI'  => ['Science Bldg',        24, true],
            'ICT'  => ['ICT Bldg',            12, true],
            'ADM'  => ['Admin Bldg',          18, true],
            'LIB'  => ['University Library',  10, true],
            'ENG'  => ['Engineering Annex',   16, false],
        ];

        foreach ($plan as $code => [$name, $target, $active]) {
            $building = Building::firstOrCreate(['code' => $code], ['name' => $name]);
            $building->update(['is_active' => $active]);

            $n = 0;
            while ($building->locations()->count() < $target) {
                $floor = intdiv($n, 16) + 1;
                Location::firstOrCreate(['building_id' => $building->id, 'name' => sprintf('%d%02d', $floor, ($n % 16) + 11)]); // 111, 112 ... 211, 212 ...
                $n++;
            }
        }
    }
}
