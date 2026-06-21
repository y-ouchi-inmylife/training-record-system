<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('client_intake_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->string('email')->nullable();
            $table->text('memo')->nullable();
            $table->timestamp('expires_at');
            $table->boolean('is_used')->default(false);
            $table->unsignedBigInteger('client_id')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->index('expires_at', 'client_intake_tokens_expires_at_idx');
            $table->index('is_used', 'client_intake_tokens_is_used_idx');
            $table->index('client_id', 'client_intake_tokens_client_id_idx');
            $table->index('created_by', 'client_intake_tokens_created_by_idx');

            $table->foreign('client_id')
                  ->references('id')
                  ->on('clients')
                  ->onDelete('set null');

            $table->foreign('created_by')
                  ->references('id')
                  ->on('counselors')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_intake_tokens');
    }
};
