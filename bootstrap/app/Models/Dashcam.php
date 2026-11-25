<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dashcam extends Model
{
    /**
     * Crockford-style alphabet: no 0/O or 1/I/L, so codes are easy to read and type.
     */
    public const CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /** A dashcam counts as online if it pinged within this many minutes. */
    public const ONLINE_WINDOW_MINUTES = 5;

    protected $fillable = [
        'code',
        'vehicle_id',
        'registered_by',
        'paired_at',
        'last_latitude',
        'last_longitude',
        'last_speed_kph',
        'last_heading',
        'last_seen_at',
    ];

    protected $casts = [
        'paired_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'last_latitude' => 'float',
        'last_longitude' => 'float',
        'last_speed_kph' => 'float',
        'last_heading' => 'float',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    /**
     * Generate a new unique code, e.g. "DC-7K2M-9QXA".
     */
    public static function generateCode(): string
    {
        do {
            $raw = '';
            $max = strlen(self::CODE_ALPHABET) - 1;
            for ($i = 0; $i < 8; $i++) {
                $raw .= self::CODE_ALPHABET[random_int(0, $max)];
            }
            $code = 'DC-' . substr($raw, 0, 4) . '-' . substr($raw, 4, 4);
        } while (self::where('code', $code)->exists());

        return $code;
    }

    /**
     * Accepts "DC-7K2M-9QXA", "dc7k2m9qxa", "DC 7K2M 9QXA" and returns the
     * canonical form, or null when the input is not a valid dashcam code.
     */
    public static function normalizeCode(?string $input): ?string
    {
        $clean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $input));

        if (!preg_match('/^DC([A-Z0-9]{8})$/', $clean, $m)) {
            return null;
        }

        return 'DC-' . substr($m[1], 0, 4) . '-' . substr($m[1], 4, 4);
    }

    public function getIsOnlineAttribute(): bool
    {
        return $this->last_seen_at
            && $this->last_seen_at->gt(now()->subMinutes(self::ONLINE_WINDOW_MINUTES));
    }
}
