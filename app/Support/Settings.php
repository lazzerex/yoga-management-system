<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Settings
{
    private const CACHE_KEY = 'settings';

    /**
     * @return array<string, string|null>
     */
    public static function all(): array
    {
        return Cache::rememberForever(
            self::CACHE_KEY,
            fn () => DB::table('settings')->pluck('value', 'key')->all()
        );
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::all()[$key] ?? null;

        return $value === null || $value === '' ? $default : $value;
    }

    public static function set(string $key, ?string $value): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => $key],
            ['value' => $value, 'updated_at' => now()]
        );

        Cache::forget(self::CACHE_KEY);
    }
}
