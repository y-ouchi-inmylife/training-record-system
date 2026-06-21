<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * audio_files テーブルに title, source カラムを追加
 *
 * title: 表示用タイトル。一覧画面で使用
 * source: データの作成経路（recording/upload/text_paste）
 *
 * @see docs/db-schema.md 3.14 audio_files, 5.18 データソース種別
 */
return new class extends Migration
{
    public function up(): void
    {
        // カラム追加
        Schema::table('audio_files', function (Blueprint $table) {
            $table->string('title', 255)
                ->nullable()
                ->after('counselor_id')
                ->comment('表示用タイトル。一覧画面で使用');
            $table->string('source', 20)
                ->after('title')
                ->comment("データの作成経路: 'recording', 'upload', 'text_paste'");
        });

        // 既存データへの値設定
        // source: 全レコードを 'recording' に設定
        DB::statement("UPDATE audio_files SET source = 'recording'");

        // title: file_name から拡張子を除去した値を設定
        // file_name が空の場合は created_at から YYYYMMDD_HHMM 形式で生成
        // CHAR_LENGTH を使用（マルチバイト文字対応）
        DB::statement("
            UPDATE audio_files
            SET title = CASE
                WHEN file_name IS NOT NULL AND file_name != '' AND LOCATE('.', file_name) > 0
                THEN LEFT(file_name, CHAR_LENGTH(file_name) - CHAR_LENGTH(SUBSTRING_INDEX(file_name, '.', -1)) - 1)
                WHEN file_name IS NOT NULL AND file_name != ''
                THEN file_name
                ELSE DATE_FORMAT(created_at, '%Y%m%d_%H%i')
            END
        ");

        // CHECK制約の追加（MySQL 8.0.16以上で強制される）
        DB::statement("ALTER TABLE audio_files ADD CONSTRAINT audio_files_source_check CHECK (source IN ('recording', 'upload', 'text_paste'))");
    }

    public function down(): void
    {
        // CHECK制約の削除
        DB::statement("ALTER TABLE audio_files DROP CONSTRAINT audio_files_source_check");

        // カラム削除
        Schema::table('audio_files', function (Blueprint $table) {
            $table->dropColumn(['title', 'source']);
        });
    }
};
