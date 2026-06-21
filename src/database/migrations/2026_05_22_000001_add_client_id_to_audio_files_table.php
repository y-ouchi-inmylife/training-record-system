<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * audio_files テーブルに client_id カラムを追加
 *
 * 背景:
 * 音声記録（AudioFile）にクライアントを紐付けるため、
 * client_id カラムを追加する（bugs.md No.177 Phase 1）。
 *
 * NOT NULL カラムとして追加するため、既存レコードは全削除する。
 * 本番リリース前のため、データ削除は許容。
 *
 * @see docs/db-schema.md 3.13 audio_files
 * @see tests/bugs.md No.177 音声記録のクライアント連動
 */
return new class extends Migration
{
    public function up(): void
    {
        // 既存レコードを全削除（NOT NULL カラム追加のため）
        // 本番リリース前のため、データ削除は許容
        DB::table('audio_files')->delete();

        Schema::table('audio_files', function (Blueprint $table) {
            // クライアントID（NOT NULL、外部キー: clients.id）
            // ON UPDATE CASCADE / ON DELETE RESTRICT
            $table->foreignId('client_id')
                ->after('counselor_id')
                ->constrained('clients')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('audio_files', function (Blueprint $table) {
            // 外部キー制約を削除してからカラムを削除
            $table->dropForeign(['client_id']);
            $table->dropColumn('client_id');
        });
    }
};
