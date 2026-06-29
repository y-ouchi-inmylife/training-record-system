<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_record_training_record', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('media_record_id');
            $table->unsignedBigInteger('training_record_id');
            $table->unsignedInteger('sort_order')->default(0)->comment('同一トレーニング記録内でのメディアの表示順（0始まりの連番、昇順）');
            // timestamps は持たない（D-0600 注記参照：紐づけ変更は親 training_records の updated_at に寄せる）

            // インデックス（複合UNIQUEは同一メディアと同一記録の重複紐づけを防止、
            // 複合INDEXはトレーニング記録から表示順でメディアを取得するため）
            $table->unique(['media_record_id', 'training_record_id'], 'mrtr_media_training_unique');
            $table->index(['training_record_id', 'sort_order'], 'mrtr_training_record_id_idx');

            // 外部キー（ともに ON DELETE CASCADE。メディア/記録の実体削除時に紐づけ行も消す）
            $table->foreign('media_record_id', 'mrtr_media_record_id_foreign')
                ->references('id')->on('media_records')->cascadeOnDelete();
            $table->foreign('training_record_id', 'mrtr_training_record_id_foreign')
                ->references('id')->on('training_records')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_record_training_record');
    }
};
