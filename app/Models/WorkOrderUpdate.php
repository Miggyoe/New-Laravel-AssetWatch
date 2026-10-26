<?php

namespace App\Models;

use App\Enums\WorkOrderStatus;
use Illuminate\Database\Eloquent\Model;

class WorkOrderUpdate extends Model
{
    protected $guarded = [];

    public function workOrder() { return $this->belongsTo(WorkOrder::class); }
    public function user()      { return $this->belongsTo(User::class); }

    public function statusEnum(): ?WorkOrderStatus
    {
        return $this->status ? WorkOrderStatus::tryFrom($this->status) : null;
    }
}
