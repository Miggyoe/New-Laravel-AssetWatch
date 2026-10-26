<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $guarded = [];

    public function user() { return $this->belongsTo(User::class); }

    public static function record(string $action, string $description, ?Model $subject = null): self
    {
        return static::create([
            'user_id'      => auth()->id(),
            'action'       => $action,
            'description'  => $description,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id'   => $subject?->getKey(),
            'ip_address'   => request()->ip(),
        ]);
    }
}
