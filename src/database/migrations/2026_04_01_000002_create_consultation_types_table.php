<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 相談内容マスタテーブル作成
     */
    public function up(): void
    {
        Schema::create('consultation_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique()->comment('相談内容の名称');
            $table->integer('sort_order')->default(0)->comment('表示順序');
            $table->timestamps();

            $table->index('sort_order', 'consultation_types_order_idx');
        });

        DB::statement("ALTER TABLE consultation_types ADD CONSTRAINT consultation_types_sort_check CHECK (sort_order >= 0)");
    }

    /**
     * ロールバック
     */
    public function down(): void
    {
        Schema::dropIfExists('consultation_types');
    }
};
