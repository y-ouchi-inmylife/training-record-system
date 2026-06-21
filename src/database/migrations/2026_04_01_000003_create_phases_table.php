<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * フェーズマスタテーブル作成
     */
    public function up(): void
    {
        Schema::create('phases', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique()->comment('フェーズの名称');
            $table->integer('sort_order')->default(0)->comment('表示順序');
            $table->timestamps();

            $table->index('sort_order', 'phases_order_idx');
        });

        DB::statement("ALTER TABLE phases ADD CONSTRAINT phases_sort_check CHECK (sort_order >= 0)");
    }

    /**
     * ロールバック
     */
    public function down(): void
    {
        Schema::dropIfExists('phases');
    }
};
