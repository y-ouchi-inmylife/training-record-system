<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audio_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trainer_id');
            $table->unsignedBigInteger('client_id');
            $table->string('title', 255);
            $table->string('source', 20)->comment("データの作成経路: 'recording', 'upload', 'text_paste'");
            // file_name のコメントは旧 create マイグレーション 2026_04_01_000015 の文言を踏襲して復活
            $table->string('file_name', 255)->nullable()->comment('元のファイル名。録音時は日時から自動生成');
            $table->string('file_path', 500)->nullable()->comment('サーバー上の保存パス（storage/audio/ 以下）。音声のみ削除時にNULL');
            $table->string('status', 20)->default('unprocessed')->comment('処理状態: unprocessed/transcribing/transcribed/summarizing/completed/error');
            $table->longText('transcription_text')->nullable()->comment('文字起こし結果。トレーナーが編集可能');
            $table->longText('summary_text')->nullable()->comment('要約結果。トレーナーが編集可能');
            $table->integer('duration_seconds')->nullable()->comment('音声の長さ（秒）。Whisper API処理時に取得');
            $table->bigInteger('file_size')->nullable()->comment('ファイルサイズ（バイト）');
            $table->timestamp('summarized_at')->nullable()->comment('要約完了日時');
            $table->timestamps();

            // インデックス（SHOW CREATE TABLE の出現順）
            $table->index('trainer_id', 'audio_records_trainer_idx');
            $table->index('status', 'audio_records_status_idx');
            $table->index('created_at', 'audio_records_created_at_idx');
            $table->index('client_id', 'audio_records_client_id_foreign');

            // 外部キー（SHOW CREATE TABLE の出現順: client_id → trainer_id）
            $table->foreign('client_id', 'audio_records_client_id_foreign')
                ->references('id')->on('clients')
                ->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('trainer_id', 'audio_records_trainer_id_foreign')
                ->references('id')->on('trainers')->cascadeOnDelete();
        });

        DB::statement("ALTER TABLE audio_records ADD CONSTRAINT audio_records_status_check CHECK (status IN ('unprocessed', 'transcribing', 'transcribed', 'summarizing', 'completed', 'error'))");
        DB::statement("ALTER TABLE audio_records ADD CONSTRAINT audio_records_duration_check CHECK (duration_seconds IS NULL OR duration_seconds >= 0)");
        DB::statement("ALTER TABLE audio_records ADD CONSTRAINT audio_records_file_size_check CHECK (file_size IS NULL OR file_size >= 0)");
        DB::statement("ALTER TABLE audio_records ADD CONSTRAINT audio_records_source_check CHECK (source IN ('recording', 'upload', 'text_paste'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('audio_records');
    }
};
