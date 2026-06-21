<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * システム設定モデル
 */
class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * IPホワイトリストを配列で取得（ip_whitelist テーブルから取得）
     */
    public static function getIpWhitelistArray(): array
    {
        return IpWhitelist::pluck('ip_address')->toArray();
    }

    /**
     * IP制限が有効かチェック
     */
    public static function isIpRestrictionEnabled(): bool
    {
        return self::where('key', 'enable_ip_restriction')->value('value') === '1';
    }

    /**
     * IPアドレスが許可されているかチェック
     */
    public static function isIpAllowed(string $ip): bool
    {
        if (!self::isIpRestrictionEnabled()) {
            return true;
        }

        $whitelist = self::getIpWhitelistArray();

        // ホワイトリストが空の場合は全許可
        if (empty($whitelist)) {
            return true;
        }

        foreach ($whitelist as $allowedIp) {
            // CIDR形式対応（例: 192.168.1.0/24）
            if (strpos($allowedIp, '/') !== false) {
                if (self::ipInRange($ip, $allowedIp)) {
                    return true;
                }
            } else {
                // 完全一致
                if ($ip === $allowedIp) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * IPアドレスがCIDR範囲内かチェック
     */
    protected static function ipInRange(string $ip, string $range): bool
    {
        [$subnet, $mask] = explode('/', $range);

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - (int)$mask);

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
}
