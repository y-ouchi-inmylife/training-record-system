<?php

namespace App\Http\Controllers;

use App\Models\IpWhitelist;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * IPアドレス制限設定コントローラー
 */
class IpRestrictionController extends Controller
{
    /**
     * IPアドレス制限設定画面
     */
    public function edit(): View
    {
        $settings = SystemSetting::pluck('value', 'key');
        $ipWhitelist = IpWhitelist::orderBy('id')->get();
        $currentIp = request()->ip();

        return view('settings.ip-restriction', compact('settings', 'ipWhitelist', 'currentIp'));
    }

    /**
     * IPアドレス制限設定の更新
     */
    public function update(Request $request): RedirectResponse
    {
        // enable_ip_restriction の保存（チェックOFF時もリストは保持）
        $enableIpRestriction = $request->has('enable_ip_restriction') ? '1' : '0';
        SystemSetting::updateOrCreate(['key' => 'enable_ip_restriction'], ['value' => $enableIpRestriction]);

        // IPアドレスリストの保存（全件洗い替え）
        $ipAddresses = $request->input('ip_addresses', []);
        $descriptions = $request->input('descriptions', []);

        $errors = [];
        $validEntries = [];
        $seenIps = [];

        foreach ($ipAddresses as $index => $ip) {
            $ip = trim($ip);
            if (empty($ip)) {
                continue;
            }

            // IPアドレス形式チェック（単一IP or CIDR）
            if (!$this->isValidIpOrCidr($ip)) {
                $errors[] = "行" . ($index + 1) . ": 「{$ip}」は無効なIPアドレス形式です。";
                continue;
            }

            // 重複チェック
            if (in_array($ip, $seenIps)) {
                $errors[] = "行" . ($index + 1) . ": 「{$ip}」が重複しています。";
                continue;
            }

            // 備考の文字数チェック（マルチバイト対応）
            $description = trim($descriptions[$index] ?? '');
            if (mb_strlen($description) > 100) {
                $errors[] = "行" . ($index + 1) . ": 備考は100文字以内で入力してください。";
                continue;
            }

            $seenIps[] = $ip;
            $validEntries[] = [
                'ip_address' => $ip,
                'description' => $description ?: null,
            ];
        }

        if (!empty($errors)) {
            return redirect()->route('settings.ip-restriction.edit')
                ->withErrors(['ip_restriction' => implode("\n", $errors)])
                ->withInput();
        }

        // トランザクションで全件洗い替え
        DB::transaction(function () use ($validEntries) {
            IpWhitelist::query()->delete();
            foreach ($validEntries as $entry) {
                IpWhitelist::create($entry);
            }
        });

        return redirect()->route('settings.ip-restriction.edit')
            ->with('success_ip', 'IPアドレス制限設定を更新しました。');
    }

    /**
     * IPアドレスまたはCIDR形式が有効かチェック
     */
    protected function isValidIpOrCidr(string $ip): bool
    {
        // CIDR形式
        if (strpos($ip, '/') !== false) {
            [$subnet, $mask] = explode('/', $ip);
            if (!filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return false;
            }
            if (!is_numeric($mask) || $mask < 0 || $mask > 32) {
                return false;
            }

            return true;
        }

        // 通常のIPアドレス
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }
}
