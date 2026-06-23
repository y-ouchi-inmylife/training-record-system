<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->date('training_date')->comment('相談日');
            $table->time('training_time')->nullable()->comment('相談時間');
            $table->unsignedBigInteger('training_type_id')->nullable();
            $table->string('training_detail', 255)->nullable()->comment('相談内容の詳細');
            $table->unsignedBigInteger('trainer1_id');
            $table->unsignedBigInteger('trainer2_id')->nullable();
            $table->text('record_content')->nullable()->comment('相談記録');
            $table->text('impression')->nullable()->comment('所感');
            $table->unsignedBigInteger('phase_id')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('updated_by')->nullable();

            // インデックス（SHOW CREATE TABLE の出現順）
            $table->index(['client_id', 'training_date'], 'training_records_client_date_idx');
            $table->index('training_date', 'training_records_date_idx');
            $table->index('trainer1_id', 'training_records_trainer1_idx');
            $table->index('trainer2_id', 'training_records_trainer2_idx');
            $table->index('training_type_id', 'training_records_type_idx');
            $table->index('phase_id', 'training_records_phase_idx');
            $table->index('updated_by', 'training_records_updated_by_foreign');

            // 外部キー（作成順）
            $table->foreign('client_id', 'training_records_client_id_foreign')
                ->references('id')->on('clients')->cascadeOnDelete();
            $table->foreign('training_type_id', 'training_records_training_type_id_foreign')
                ->references('id')->on('training_types')->nullOnDelete();
            $table->foreign('trainer1_id', 'training_records_trainer1_id_foreign')
                ->references('id')->on('trainers')->restrictOnDelete();
            $table->foreign('trainer2_id', 'training_records_trainer2_id_foreign')
                ->references('id')->on('trainers')->nullOnDelete();
            $table->foreign('phase_id', 'training_records_phase_id_foreign')
                ->references('id')->on('phases')->nullOnDelete();
            $table->foreign('updated_by', 'training_records_updated_by_foreign')
                ->references('id')->on('trainers')->nullOnDelete();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('training_records');
    }
};
