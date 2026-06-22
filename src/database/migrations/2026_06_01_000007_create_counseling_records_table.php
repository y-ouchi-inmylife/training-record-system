<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counseling_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->date('consultation_date')->comment('相談日');
            $table->time('consultation_time')->nullable()->comment('相談時間');
            $table->boolean('is_intake')->default(false)->comment('インテークフラグ');
            $table->boolean('is_followup')->default(false)->comment('フォローアップフラグ');
            $table->unsignedBigInteger('consultation_type_id')->nullable();
            $table->string('consultation_detail', 255)->nullable()->comment('相談内容の詳細');
            $table->unsignedBigInteger('counselor1_id');
            $table->unsignedBigInteger('counselor2_id')->nullable();
            $table->text('record_content')->nullable()->comment('相談記録');
            $table->text('impression')->nullable()->comment('所感');
            $table->unsignedBigInteger('phase_id')->nullable();
            $table->string('attendance', 20);
            $table->string('consultation_format', 20);
            $table->string('consultation_format_detail', 255)->nullable()->comment('相談形態の詳細');
            $table->timestamps();
            $table->unsignedBigInteger('updated_by')->nullable();

            // インデックス（SHOW CREATE TABLE の出現順）
            $table->index(['client_id', 'consultation_date'], 'counseling_records_client_date_idx');
            $table->index('consultation_date', 'counseling_records_date_idx');
            $table->index('counselor1_id', 'counseling_records_counselor1_idx');
            $table->index('counselor2_id', 'counseling_records_counselor2_idx');
            $table->index('consultation_type_id', 'counseling_records_type_idx');
            $table->index('phase_id', 'counseling_records_phase_idx');
            $table->index('updated_by', 'counseling_records_updated_by_foreign');

            // 外部キー（作成順）
            $table->foreign('client_id', 'counseling_records_client_id_foreign')
                ->references('id')->on('clients')->cascadeOnDelete();
            $table->foreign('consultation_type_id', 'counseling_records_consultation_type_id_foreign')
                ->references('id')->on('consultation_types')->nullOnDelete();
            $table->foreign('counselor1_id', 'counseling_records_counselor1_id_foreign')
                ->references('id')->on('counselors')->restrictOnDelete();
            $table->foreign('counselor2_id', 'counseling_records_counselor2_id_foreign')
                ->references('id')->on('counselors')->nullOnDelete();
            $table->foreign('phase_id', 'counseling_records_phase_id_foreign')
                ->references('id')->on('phases')->nullOnDelete();
            $table->foreign('updated_by', 'counseling_records_updated_by_foreign')
                ->references('id')->on('counselors')->nullOnDelete();
        });

        // CHECK 制約（全角文字は既存マイグレーションから正確にコピー）
        DB::statement("ALTER TABLE counseling_records ADD CONSTRAINT counseling_records_attendance_check CHECK (attendance IN ('参加', 'キャンセル（連絡あり）', 'キャンセル（連絡なし）'))");
        DB::statement("ALTER TABLE counseling_records ADD CONSTRAINT counseling_records_format_check CHECK (consultation_format IN ('対面', 'ビデオ通話', '電話', 'メール', '同行', '訪問', 'その他'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('counseling_records');
    }
};
