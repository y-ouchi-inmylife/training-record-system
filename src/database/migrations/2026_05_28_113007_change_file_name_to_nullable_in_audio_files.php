<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. file_name を NULL 許容に変更（comment 属性は維持するため生SQL使用）
        DB::statement('ALTER TABLE audio_files MODIFY file_name VARCHAR(255) NULL');

        // 2. 既存の text_paste レコードの file_name を NULL に更新
        //    （title と同値のコピーが入っており、業務的な意味がないため）
        DB::statement("UPDATE audio_files SET file_name = NULL WHERE source = 'text_paste'");
    }

    public function down(): void
    {
        // 1. text_paste レコードの file_name を title からコピーして復元
        DB::statement("UPDATE audio_files SET file_name = title WHERE source = 'text_paste' AND file_name IS NULL");

        // 2. NOT NULL に戻す
        DB::statement('ALTER TABLE audio_files MODIFY file_name VARCHAR(255) NOT NULL');
    }
};
