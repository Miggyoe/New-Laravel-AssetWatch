<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Development only: the seven categories and descriptions from the design.
 *   php artisan db:seed --class=DemoCategoriesSeeder
 * Existing categories keep their id (so reports stay linked); descriptions are filled in only when empty.
 */
class DemoCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Furniture'             => 'Chairs, desks, cabinets, and seating.',
            'Electrical Facilities' => 'Lighting, outlets, switches, and wiring.',
            'Computer Equipment'    => 'Computers, peripherals, and projectors.',
            'Laboratory Equipment'  => 'Lab tables, fixtures, and teaching equipment.',
            'Plumbing'              => 'Faucets, drains, pipes, and restroom fixtures.',
            'Classroom Facilities'  => 'Doors, windows, boards, and classroom fittings.',
            'Other Campus Assets'   => 'Campus assets not covered by other categories.',
        ];

        foreach ($categories as $name => $description) {
            $category = Category::firstOrCreate(['name' => $name]);

            if (! $category->description) {
                $category->update(['description' => $description]);
            }
        }
    }
}
