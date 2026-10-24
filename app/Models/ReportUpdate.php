<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ReportUpdate extends Model
{
    protected $guarded = [];

    public function report() { return $this->belongsTo(Report::class); }
    public function user()   { return $this->belongsTo(User::class); }

    /** Headline for the dashboard's "Maintenance activity" feed. */
    public function headline(): string
    {
        return match (true) {
            $this->from_status === null       => 'New incident submitted',
            $this->to_status === 'verified'   => 'Report verified by ' . Str::before($this->user?->name ?? 'admin', ' '),
            $this->to_status === 'in_progress' => 'Repair in progress',
            $this->to_status === 'resolved'   => ($this->report?->title ?? 'Report') . ' resolved',
            $this->to_status === 'rejected'   => 'Report rejected',
            default                           => 'Report updated',
        };
    }

    public function icon(): string
    {
        return match ($this->to_status) {
            'verified'    => 'shield-check',
            'in_progress' => 'user-check',
            'resolved'    => 'circle-check',
            default       => 'clipboard-plus',
        };
    }

    public function when(): string
    {
        return $this->created_at->isToday()
            ? $this->created_at->diffForHumans()
            : $this->created_at->format('j M, g:i A');
    }
}
