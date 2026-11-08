<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Building extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function locations() { return $this->hasMany(Location::class); }

    /** Kept for the compact "Building · Room" labels; real building names are already short. */
    protected function shortName(): Attribute
    {
        return Attribute::get(fn () => (string) $this->name);
    }
}
