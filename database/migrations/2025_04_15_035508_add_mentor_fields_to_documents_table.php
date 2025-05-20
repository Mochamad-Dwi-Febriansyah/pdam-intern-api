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
        Schema::table('documents', function (Blueprint $table) {
            $table->string('mentor_name')->nullable()->after('mentor_id');
            $table->string('mentor_rank_group')->nullable()->after('mentor_name');
            $table->string('mentor_position')->nullable()->after('mentor_rank_group');
            $table->string('mentor_nik')->nullable()->after('mentor_position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn([
                'mentor_name',
                'mentor_rank_group',
                'mentor_position',
                'mentor_nik',
            ]);
        });
    }
};
