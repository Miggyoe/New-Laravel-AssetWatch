<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    /** Room types and the pill colours used for them. */
    public const TYPES = [
        'Classroom'  => 'bg-[#eff6ff] text-[#2563eb]',
        'Office'     => 'bg-[#f3eeff] text-[#7c3aed]',
        'Laboratory' => 'bg-[#e8f7f3] text-[#0f9d8a]',
        'Facility'   => 'bg-[#fff7e0] text-[#a16207]',
        'Restroom'   => 'bg-[#f5f7fb] text-[#64748b]',
        'Other'      => 'bg-[#f5f7fb] text-[#64748b]',
    ];

    protected $guarded = [];

    public function building() { return $this->belongsTo(Building::class); }
    public function reports()  { return $this->hasMany(Report::class); }

    /** "MB101", or the name when the room has no code ("Library"). */
    public function shortLabel(): string
    {
        return $this->code ?: $this->name;
    }

    /** "MB101 – MIS Office", or just the name when there is no code. */
    public function fullLabel(): string
    {
        return $this->code ? $this->code . ' – ' . $this->name : $this->name;
    }

    public function typeBadge(): string
    {
        return self::TYPES[$this->type] ?? self::TYPES['Other'];
    }
}
