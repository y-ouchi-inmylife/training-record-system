<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // counseling_records_client_idx は counseling_records_client_date_idx
        // （複合インデックス）の先頭カラムで完全に代替されるため削除
        Schema::table('counseling_records', function (Blueprint $table) {
            $table->dropIndex('counseling_records_client_idx');
        });
    }

    public function down(): void
    {
        Schema::table('counseling_records', function (Blueprint $table) {
            $table->index('client_id', 'counseling_records_client_idx');
        });
    }
};
