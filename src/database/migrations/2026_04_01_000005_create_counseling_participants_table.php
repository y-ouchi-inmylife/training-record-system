<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 相談参加者テーブル作成
     */
    public function up(): void
    {
        Schema::create('counseling_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('counseling_record_id')->constrained('counseling_records')->cascadeOnDelete();
            $table->string('participant_type', 10)->comment('参加者区分');
            $table->string('participant_detail', 255)->nullable()->comment('参加者の詳細');
            $table->timestamp('created_at')->useCurrent();

            $table->index('counseling_record_id', 'counseling_participants_record_idx');
        });

        DB::statement("ALTER TABLE counseling_participants ADD CONSTRAINT counseling_participants_type_check CHECK (participant_type IN ('本人', '家族', '支援者', 'その他'))");
    }

    /**
     * ロールバック
     */
    public function down(): void
    {
        Schema::dropIfExists('counseling_participants');
    }
};
