<?php

namespace App\Enums;

enum PersonnelAvailability: string
{
    case Available   = 'available';
    case Busy        = 'busy';
    case Unavailable = 'unavailable';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::Available   => 'bg-[#e8f7f3] text-[#0f9d8a]',
            self::Busy        => 'bg-[#fff7e0] text-[#a16207]',
            self::Unavailable => 'bg-[#eff6ff] text-[#2563eb]',
        };
    }
}
