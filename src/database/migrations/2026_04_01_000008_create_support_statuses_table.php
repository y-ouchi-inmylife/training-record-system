<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 支援状態マスタテーブル作成
     */
    public function up(): void
    {
        Schema::create('support_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique()->comment('支援状態の名称');
            $table->integer('sort_order')->default(0)->comment('表示順序');
            $table->timestamps();

            $table->index('sort_order', 'support_statuses_order_idx');
        });

        DB::statement("ALTER TABLE support_statuses ADD CONSTRAINT support_statuses_sort_check CHECK (sort_order >= 0)");
    }

    /**
     * ロールバック
     */
    public function down(): void
    {
        Schema::dropIfExists('support_statuses');
    }
};
