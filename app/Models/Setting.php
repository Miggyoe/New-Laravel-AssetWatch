<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    public const DEFAULTS = [
        'campus.institution'        => 'MCC · Main Campus',
        'campus.helpdesk_extension' => '200',
        'system.target_days'        => '3',
        'system.evidence_limit'     => '10 MB · JPG, PNG, PDF',
    ];

    protected $guarded = [];

    public static function get(string $key, ?string $default = null): ?string
    {
        $all = Cache::rememberForever('settings', fn () => static::query()->pluck('value', 'key')->all());

        return $all[$key] ?? $default ?? (self::DEFAULTS[$key] ?? null);
    }

    /** @param array<string, string|null> $values */
    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget('settings');
    }

    /** Today plus the configured number of working days (Monday to Friday). */
    public static function defaultTargetDate(): Carbon
    {
        $days = max(1, (int) static::get('system.target_days'));
        $date = now();

        while ($days > 0) {
            $date = $date->addDay();
            if (! $date->isWeekend()) {
                $days--;
            }
        }

        return $date;
    }
}
