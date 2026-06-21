<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. NOT NULL 化
        Schema::table('counseling_records', function (Blueprint $table) {
            $table->string('attendance', 20)->nullable(false)->change();
            $table->string('consultation_format', 20)->nullable(false)->change();
        });

        // 2. CHECK 制約を厳密化（IS NULL OR を削除）
        DB::statement('ALTER TABLE counseling_records DROP CHECK counseling_records_attendance_check');
        DB::statement("
            ALTER TABLE counseling_records
            ADD CONSTRAINT counseling_records_attendance_check
            CHECK (
                attendance IN ('参加', 'キャンセル（連絡あり）', 'キャンセル（連絡なし）')
            )
        ");

        DB::statement('ALTER TABLE counseling_records DROP CHECK counseling_records_format_check');
        DB::statement("
            ALTER TABLE counseling_records
            ADD CONSTRAINT counseling_records_format_check
            CHECK (
                consultation_format IN ('対面', 'ビデオ通話', '電話', 'メール', '同行', '訪問', 'その他')
            )
        ");
    }

    public function down(): void
    {
        // 1. CHECK 制約を IS NULL OR 含む形に戻す
        DB::statement('ALTER TABLE counseling_records DROP CHECK counseling_records_attendance_check');
        DB::statement("
            ALTER TABLE counseling_records
            ADD CONSTRAINT counseling_records_attendance_check
            CHECK (
                attendance IS NULL OR
                attendance IN ('参加', 'キャンセル（連絡あり）', 'キャンセル（連絡なし）')
            )
        ");

        DB::statement('ALTER TABLE counseling_records DROP CHECK counseling_records_format_check');
        DB::statement("
            ALTER TABLE counseling_records
            ADD CONSTRAINT counseling_records_format_check
            CHECK (
                consultation_format IS NULL OR
                consultation_format IN ('対面', 'ビデオ通話', '電話', 'メール', '同行', '訪問', 'その他')
            )
        ");

        // 2. NULL 許容に戻す
        Schema::table('counseling_records', function (Blueprint $table) {
            $table->string('attendance', 20)->nullable()->change();
            $table->string('consultation_format', 20)->nullable()->change();
        });
    }
};
