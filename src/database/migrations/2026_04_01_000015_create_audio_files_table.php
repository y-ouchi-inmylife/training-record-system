<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 音声ファイルテーブルの作成
 *
 * 録音・アップロードされた音声ファイルの情報と、
 * 文字起こし・要約の処理結果を管理する。
 *
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audio_files', function (Blueprint $table) {
            // 主キー
            $table->id();

            // アップロード・録音したカウンセラーのID
            $table->foreignId('counselor_id')
                ->constrained('counselors')
                ->cascadeOnDelete();

            // ファイル情報
            $table->string('file_name', 255)
                ->comment('元のファイル名。録音時は日時から自動生成');
            $table->string('file_path', 500)
                ->nullable()
                ->comment('サーバー上の保存パス（storage/audio/ 以下）。音声のみ削除時にNULL');

            // 処理状態
            $table->string('status', 20)
                ->default('unprocessed')
                ->comment('処理状態: unprocessed/transcribing/transcribed/summarizing/completed/error');

            // 文字起こし・要約テキスト
            $table->longText('transcription_text')
                ->nullable()
                ->comment('文字起こし結果。カウンセラーが編集可能');
            $table->longText('summary_text')
                ->nullable()
                ->comment('要約結果。カウンセラーが編集可能');

            // メタデータ
            $table->integer('duration_seconds')
                ->nullable()
                ->comment('音声の長さ（秒）。Whisper API処理時に取得');
            $table->bigInteger('file_size')
                ->nullable()
                ->comment('ファイルサイズ（バイト）');

            // 要約完了日時
            $table->timestamp('summarized_at')
                ->nullable()
                ->comment('要約完了日時');

            $table->timestamps();

            // インデックス
            $table->index('counselor_id', 'audio_files_counselor_idx');
            $table->index('status', 'audio_files_status_idx');
            $table->index(['created_at'], 'audio_files_created_at_idx');
        });

        // CHECK制約の追加（MySQL 8.0対応）
        DB::statement("ALTER TABLE audio_files ADD CONSTRAINT audio_files_status_check CHECK (status IN ('unprocessed', 'transcribing', 'transcribed', 'summarizing', 'completed', 'error'))");
        DB::statement("ALTER TABLE audio_files ADD CONSTRAINT audio_files_duration_check CHECK (duration_seconds IS NULL OR duration_seconds >= 0)");
        DB::statement("ALTER TABLE audio_files ADD CONSTRAINT audio_files_file_size_check CHECK (file_size IS NULL OR file_size >= 0)");
    }

    public function down(): void
    {
        Schema::dropIfExists('audio_files');
    }
};
