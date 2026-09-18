<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. training_records の外部キー・インデックス・カラムを削除
        Schema::table('training_records', function (Blueprint $table) {
            $table->dropForeign('training_records_training_type_id_foreign');
            $table->dropIndex('training_records_type_idx');
            $table->dropColumn(['training_type_id', 'training_detail']);
        });

        // 2. training_types テーブルを削除
        Schema::dropIfExists('training_types');
    }

    public function down(): void
    {
        // 注意: 構造だけを元に戻す。データ（training_types の 3 件・
        // training_records の 2 カラムの値）は復元されない。
        Schema::create('training_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique()->comment('相談内容の名称');
            $table->integer('sort_order')->default(0)->comment('表示順序');
            $table->timestamps();

            $table->index('sort_order', 'training_types_order_idx');
        });

        DB::statement("ALTER TABLE training_types ADD CONSTRAINT training_types_sort_check CHECK (sort_order >= 0)");

        Schema::table('training_records', function (Blueprint $table) {
            // 元の並び順（training_time の後、trainer1_id の前）に戻す
            $table->unsignedBigInteger('training_type_id')->nullable()->after('training_time');
            $table->string('training_detail', 255)->nullable()->after('training_type_id')->comment('相談内容の詳細');

            $table->index('training_type_id', 'training_records_type_idx');
            $table->foreign('training_type_id', 'training_records_training_type_id_foreign')
                ->references('id')->on('training_types')->nullOnDelete();
        });
    }
};
