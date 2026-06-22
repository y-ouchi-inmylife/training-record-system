<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 参加者は相談記録に従属するため、独自のタイムスタンプ（created_at/updated_at）を持たない設計
        Schema::create('counseling_participants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('counseling_record_id');
            $table->string('participant_type', 10)->comment('参加者区分');
            $table->string('participant_detail', 255)->nullable()->comment('参加者の詳細');

            $table->index('counseling_record_id', 'counseling_participants_record_idx');

            $table->foreign('counseling_record_id', 'counseling_participants_counseling_record_id_foreign')
                ->references('id')->on('counseling_records')->cascadeOnDelete();
        });

        DB::statement("ALTER TABLE counseling_participants ADD CONSTRAINT counseling_participants_type_check CHECK (participant_type IN ('本人', '支援者', '母', '父', '配偶者', 'きょうだい', '子', '祖父母', 'その他'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('counseling_participants');
    }
};
