<?php

namespace App\Enums;

/** Order matters: it is the order of the summary cards on the Work Orders page. */
enum WorkOrderStatus: string
{
    case Assigned   = 'assigned';
    case Accepted   = 'accepted';
    case InProgress = 'in_progress';
    case Completed  = 'completed';
    case Cancelled  = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'In Progress',
            default          => ucfirst($this->value),
        };
    }

    /** Pill classes (background + text) from the Figma badges. */
    public function badge(): string
    {
        return match ($this) {
            self::Assigned, self::InProgress => 'bg-[#eff6ff] text-[#2563eb]',
            self::Accepted                   => 'bg-[#e8f7f3] text-[#0f9d8a]',
            self::Completed                  => 'bg-[#eaf7ee] text-[#15803d]',
            self::Cancelled                  => 'bg-[#f5f7fb] text-[#64748b]',
        };
    }

    /** Still needs work from a technician. */
    public function isOpen(): bool
    {
        return in_array($this, [self::Assigned, self::Accepted, self::InProgress], true);
    }
}
