<?php

namespace App\Enums;

enum ReportPriority: string
{
    case Low    = 'low';
    case Medium = 'medium';
    case High   = 'high';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::Low    => 'bg-[#f5f7fb] text-[#64748b]',
            self::Medium => 'bg-[#fff7e0] text-[#a16207]',
            self::High   => 'bg-[#fef2f2] text-[#b91c1c]',
        };
    }
}
