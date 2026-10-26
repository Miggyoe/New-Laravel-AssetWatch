<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Technician extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function user()       { return $this->belongsTo(User::class); }
    public function workOrders() { return $this->hasMany(WorkOrder::class); }

    /** "Ramon Dela Cruz · Carpentry" */
    public function label(): string
    {
        return $this->name . ' · ' . $this->specialty;
    }
}
