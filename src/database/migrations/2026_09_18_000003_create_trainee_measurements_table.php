<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainee_measurements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trainee_id')->comment('トレーニーのID（外部キー）');
            $table->date('measured_date')->comment('計測日');
            $table->time('measured_time')->comment('計測時刻（HH:MM 形式。1日2回の計測に対応するため NOT NULL）');
            $table->decimal('weight_kg', 5, 2)->comment('体重（kg）');
            $table->string('note', 255)->nullable()->comment('備考');
            $table->timestamps();
            $table->unsignedBigInteger('updated_by')->nullable();

            // インデックス
            $table->unique(['trainee_id', 'measured_date', 'measured_time'], 'trainee_measurements_trainee_date_unique');
            $table->index('updated_by', 'trainee_measurements_updated_by_foreign');

            // 外部キー
            $table->foreign('trainee_id', 'trainee_measurements_trainee_id_foreign')
                ->references('id')->on('trainees')->cascadeOnDelete();
            $table->foreign('updated_by', 'trainee_measurements_updated_by_foreign')
                ->references('id')->on('trainers')->nullOnDelete();
        });

        // CHECK 制約（Laravel schema builder では表現できないため DB::statement で追加）
        DB::statement('ALTER TABLE trainee_measurements ADD CONSTRAINT trainee_measurements_weight_kg_check CHECK (weight_kg > 0 AND weight_kg <= 999.99)');
    }

    public function down(): void
    {
        Schema::dropIfExists('trainee_measurements');
    }
};
