<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Réglage de la plateforme (clé → valeur), modifiable par le Super Admin.
 * Lu une fois puis gardé en cache ; le cache est vidé à chaque modification.
 */
class PlatformSetting extends Model
{
    public const CACHE_KEY = 'platform-settings';

    /** Numéro WhatsApp du service client (format 0700000000). */
    public const SUPPORT_WHATSAPP = 'support_whatsapp';

    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];

    /** @return array<string, string|null> */
    public static function values(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->pluck('value', 'key')->all());
    }

    public static function read(string $key, ?string $default = null): ?string
    {
        $value = static::values()[$key] ?? null;

        return $value !== null && $value !== '' ? $value : $default;
    }

    public static function write(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget(self::CACHE_KEY);
    }
}
