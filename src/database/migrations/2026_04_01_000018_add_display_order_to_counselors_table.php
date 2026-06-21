<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * カウンセラーに表示順カラムを追加
     */
    public function up(): void
    {
        Schema::table('counselors', function (Blueprint $table) {
            $table->integer('display_order')->default(0)->after('must_change_password');
            $table->index('display_order', 'counselors_display_order_index');
        });

        // system_adminは0、それ以外はid順で1,2,3...を設定
        DB::table('counselors')->where('role', 'system_admin')->update(['display_order' => 0]);
        $counselors = DB::table('counselors')->where('role', '!=', 'system_admin')->orderBy('id')->get();
        foreach ($counselors as $index => $counselor) {
            DB::table('counselors')
                ->where('id', $counselor->id)
                ->update(['display_order' => $index + 1]);
        }
    }

    public function down(): void
    {
        Schema::table('counselors', function (Blueprint $table) {
            $table->dropIndex('counselors_display_order_index');
            $table->dropColumn('display_order');
        });
    }
};
