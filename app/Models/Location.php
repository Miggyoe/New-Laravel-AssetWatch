<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $guarded = [];

    public function building() { return $this->belongsTo(Building::class); }
    public function reports()  { return $this->hasMany(Report::class); }
}
