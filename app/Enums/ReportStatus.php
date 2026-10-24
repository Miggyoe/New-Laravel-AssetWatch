<?php

namespace App\Enums;

/** Order matters: it is the order of the donut segments and legend on the dashboard. */
enum ReportStatus: string
{
    case Pending    = 'pending';
    case Verified   = 'verified';
    case InProgress = 'in_progress';
    case Resolved   = 'resolved';
    case Rejected   = 'rejected';

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
            self::Pending    => 'bg-[#fff7e0] text-[#a16207]',
            self::Verified   => 'bg-[#f3eeff] text-[#7c3aed]',
            self::InProgress => 'bg-[#eff6ff] text-[#2563eb]',
            self::Resolved   => 'bg-[#eaf7ee] text-[#15803d]',
            self::Rejected   => 'bg-[#fef2f2] text-[#b91c1c]',
        };
    }

    /** Solid colour used by the donut chart and legend dots. */
    public function color(): string
    {
        return match ($this) {
            self::Pending    => '#a16207',
            self::Verified   => '#7c3aed',
            self::InProgress => '#2563eb',
            self::Resolved   => '#0f9d8a',
            self::Rejected   => '#b91c1c',
        };
    }
}
