<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * audio_files テーブルを audio_records にリネーム（段階的リネームの第1段階：DB層のみ）
 *
 * 業務概念「音声記録」（実音声＋文字起こしテキスト＋要約テキスト＋メタデータの統合体）に
 * テーブル名を一致させる。モデルクラス名（AudioFile）、ルートパス（/audio-files）、
 * 設計書文言の更新は後続ステップで対応する。
 *
 * 他テーブルから audio_files を参照する外部キーは存在しないため、参照側の制約変更は不要。
 *
 * 順序:
 *   up() = テーブル名 → FK削除 → インデックス名 → FK再作成 → CHECK名
 *   down() = CHECK名戻す → FK削除 → インデックス名戻す → FK再作成 → テーブル名戻す
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. テーブル名をリネーム（インデックス・FK・CHECK は古い名前のまま残る）
        Schema::rename('audio_files', 'audio_records');

        // 2. 外部キー制約を一旦削除（インデックスは残る）
        //    インデックス名のリネーム前に FK を外しておく方が、依存関係エラーのリスクが低い
        DB::statement('ALTER TABLE audio_records DROP FOREIGN KEY audio_files_counselor_id_foreign');
        DB::statement('ALTER TABLE audio_records DROP FOREIGN KEY audio_files_client_id_foreign');

        // 3. インデックスをリネーム
        //    audio_files_client_id_foreign は FK 作成時に MySQL が自動生成した index（同名）
        DB::statement('ALTER TABLE audio_records RENAME INDEX audio_files_counselor_idx TO audio_records_counselor_idx');
        DB::statement('ALTER TABLE audio_records RENAME INDEX audio_files_status_idx TO audio_records_status_idx');
        DB::statement('ALTER TABLE audio_records RENAME INDEX audio_files_created_at_idx TO audio_records_created_at_idx');
        DB::statement('ALTER TABLE audio_records RENAME INDEX audio_files_client_id_foreign TO audio_records_client_id_foreign');

        // 4. 外部キー制約を新名で再作成（既存インデックスを再利用）
        DB::statement('ALTER TABLE audio_records ADD CONSTRAINT audio_records_counselor_id_foreign FOREIGN KEY (counselor_id) REFERENCES counselors(id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE audio_records ADD CONSTRAINT audio_records_client_id_foreign FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT ON UPDATE CASCADE');

        // 5. CHECK 制約を新名で再作成（MySQL は CHECK rename をサポートしないため DROP + ADD）
        DB::statement('ALTER TABLE audio_records DROP CHECK audio_files_status_check');
        DB::statement("ALTER TABLE audio_records ADD CONSTRAINT audio_records_status_check CHECK (status IN ('unprocessed', 'transcribing', 'transcribed', 'summarizing', 'completed', 'error'))");

        DB::statement('ALTER TABLE audio_records DROP CHECK audio_files_duration_check');
        DB::statement('ALTER TABLE audio_records ADD CONSTRAINT audio_records_duration_check CHECK (duration_seconds IS NULL OR duration_seconds >= 0)');

        DB::statement('ALTER TABLE audio_records DROP CHECK audio_files_file_size_check');
        DB::statement('ALTER TABLE audio_records ADD CONSTRAINT audio_records_file_size_check CHECK (file_size IS NULL OR file_size >= 0)');

        DB::statement('ALTER TABLE audio_records DROP CHECK audio_files_source_check');
        DB::statement("ALTER TABLE audio_records ADD CONSTRAINT audio_records_source_check CHECK (source IN ('recording', 'upload', 'text_paste'))");
    }

    public function down(): void
    {
        // 5. CHECK 制約を旧名に戻す
        DB::statement('ALTER TABLE audio_records DROP CHECK audio_records_source_check');
        DB::statement("ALTER TABLE audio_records ADD CONSTRAINT audio_files_source_check CHECK (source IN ('recording', 'upload', 'text_paste'))");

        DB::statement('ALTER TABLE audio_records DROP CHECK audio_records_file_size_check');
        DB::statement('ALTER TABLE audio_records ADD CONSTRAINT audio_files_file_size_check CHECK (file_size IS NULL OR file_size >= 0)');

        DB::statement('ALTER TABLE audio_records DROP CHECK audio_records_duration_check');
        DB::statement('ALTER TABLE audio_records ADD CONSTRAINT audio_files_duration_check CHECK (duration_seconds IS NULL OR duration_seconds >= 0)');

        DB::statement('ALTER TABLE audio_records DROP CHECK audio_records_status_check');
        DB::statement("ALTER TABLE audio_records ADD CONSTRAINT audio_files_status_check CHECK (status IN ('unprocessed', 'transcribing', 'transcribed', 'summarizing', 'completed', 'error'))");

        // 4. 外部キー制約を一旦削除
        DB::statement('ALTER TABLE audio_records DROP FOREIGN KEY audio_records_client_id_foreign');
        DB::statement('ALTER TABLE audio_records DROP FOREIGN KEY audio_records_counselor_id_foreign');

        // 3. インデックスを旧名に戻す
        DB::statement('ALTER TABLE audio_records RENAME INDEX audio_records_client_id_foreign TO audio_files_client_id_foreign');
        DB::statement('ALTER TABLE audio_records RENAME INDEX audio_records_created_at_idx TO audio_files_created_at_idx');
        DB::statement('ALTER TABLE audio_records RENAME INDEX audio_records_status_idx TO audio_files_status_idx');
        DB::statement('ALTER TABLE audio_records RENAME INDEX audio_records_counselor_idx TO audio_files_counselor_idx');

        // 2. 外部キー制約を旧名で再作成
        DB::statement('ALTER TABLE audio_records ADD CONSTRAINT audio_files_client_id_foreign FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT ON UPDATE CASCADE');
        DB::statement('ALTER TABLE audio_records ADD CONSTRAINT audio_files_counselor_id_foreign FOREIGN KEY (counselor_id) REFERENCES counselors(id) ON DELETE CASCADE');

        // 1. テーブル名を旧名に戻す
        Schema::rename('audio_records', 'audio_files');
    }
};
