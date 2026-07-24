<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

final class BlockedIp extends Model
{
    protected $fillable = [
        'ip_address',
        'reason',
        'blocked_by',
    ];

    /**
     * Check if an IP address is blocked.
     */
    public static function isBlocked(?string $ip): bool
    {
        if (empty($ip)) {
            return false;
        }

        return Cache::remember('blocked_ip:' . $ip, 86400, function () use ($ip) {
            return self::where('ip_address', $ip)->exists();
        });
    }

    /**
     * Block an IP address.
     */
    public static function block(string $ip, ?string $reason = null, ?int $userId = null): self
    {
        $blockedIp = self::updateOrCreate(
            ['ip_address' => $ip],
            [
                'reason' => $reason,
                'blocked_by' => $userId,
            ]
        );

        Cache::put('blocked_ip:' . $ip, true, 86400);

        return $blockedIp;
    }

    /**
     * Unblock an IP address.
     */
    public static function unblock(string $ip): void
    {
        self::where('ip_address', $ip)->delete();

        Cache::forget('blocked_ip:' . $ip);
    }
}
