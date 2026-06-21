<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * system_settings から ip_whitelist レコードを削除
     */
    public function up(): void
    {
        DB::table('system_settings')->where('key', 'ip_whitelist')->delete();
    }

    public function down(): void
    {
        DB::table('system_settings')->insertOrIgnore([
            'key' => 'ip_whitelist',
            'value' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
