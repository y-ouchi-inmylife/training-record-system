<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 相談記録テーブル作成
     */
    public function up(): void
    {
        Schema::create('counseling_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->date('consultation_date')->comment('相談日');
            $table->time('consultation_time')->nullable()->comment('相談時間');
            $table->boolean('is_intake')->default(false)->comment('インテークフラグ');
            $table->boolean('is_followup')->default(false)->comment('フォローアップフラグ');
            $table->foreignId('consultation_type_id')->nullable()->constrained('consultation_types')->nullOnDelete();
            $table->string('consultation_detail', 255)->nullable()->comment('相談内容の詳細');
            $table->foreignId('counselor1_id')->constrained('counselors')->restrictOnDelete();
            $table->foreignId('counselor2_id')->nullable()->constrained('counselors')->nullOnDelete();
            $table->text('record_content')->nullable()->comment('相談記録');
            $table->text('impression')->nullable()->comment('所感');
            $table->foreignId('phase_id')->nullable()->constrained('phases')->nullOnDelete();
            $table->string('attendance', 20)->nullable()->comment('出欠状況');
            $table->string('consultation_format', 20)->nullable()->comment('相談形態');
            $table->string('consultation_format_detail', 255)->nullable()->comment('相談形態の詳細');
            $table->timestamps();

            // インデックス
            $table->index('client_id', 'counseling_records_client_idx');
            $table->index(['client_id', 'consultation_date'], 'counseling_records_client_date_idx');
            $table->index('consultation_date', 'counseling_records_date_idx');
            $table->index('counselor1_id', 'counseling_records_counselor1_idx');
            $table->index('counselor2_id', 'counseling_records_counselor2_idx');
            $table->index('consultation_type_id', 'counseling_records_type_idx');
            $table->index('phase_id', 'counseling_records_phase_idx');
        });

        // CHECK制約を追加
        DB::statement("ALTER TABLE counseling_records ADD CONSTRAINT counseling_records_attendance_check CHECK (attendance IS NULL OR attendance IN ('参加', 'キャンセル（連絡あり）', 'キャンセル（連絡なし）'))");
        DB::statement("ALTER TABLE counseling_records ADD CONSTRAINT counseling_records_format_check CHECK (consultation_format IS NULL OR consultation_format IN ('対面', 'ビデオ通話', '電話', 'メール', '同行', '訪問', 'その他'))");
    }

    /**
     * ロールバック
     */
    public function down(): void
    {
        Schema::dropIfExists('counseling_records');
    }
};
