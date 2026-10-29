<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Building extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function locations() { return $this->hasMany(Location::class); }

    /** "Academic Bldg" -> "Academic" (used in compact location labels such as "Academic · 203"). */
    protected function shortName(): Attribute
    {
        return Attribute::get(fn () => Str::of($this->name)
            ->replaceMatches('/\s+(Bldg\.?|Building)$/i', '')
            ->toString());
    }
}
