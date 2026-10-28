<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportNote extends Model
{
    protected $guarded = [];

    public function report() { return $this->belongsTo(Report::class); }
    public function user()   { return $this->belongsTo(User::class); }
}
