<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ip_whitelist テーブルを作成し、既存データを移行
     */
    public function up(): void
    {
        Schema::create('ip_whitelist', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45);
            $table->string('description', 100)->nullable();
            $table->timestamps();

            $table->unique('ip_address');
        });

        // 既存データの移行（system_settings.ip_whitelist → ip_whitelist テーブル）
        $whitelist = DB::table('system_settings')
            ->where('key', 'ip_whitelist')
            ->value('value');

        if (!empty($whitelist)) {
            $ips = array_filter(array_map('trim', explode("\n", $whitelist)));
            foreach ($ips as $ip) {
                if (!empty($ip)) {
                    DB::table('ip_whitelist')->insertOrIgnore([
                        'ip_address' => $ip,
                        'description' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_whitelist');
    }
};
