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
        Schema::create('certificate_fields', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('certificate_id');
            $table->foreign('certificate_id')->references('id')->on('certificates')->cascadeOnDelete()->cascadeOnUpdate();
            $table->uuid('assessment_aspects_id');
            $table->foreign('assessment_aspects_id')->references('id')->on('assessment_aspects')->cascadeOnDelete()->cascadeOnUpdate();
            $table->decimal('score', 10, 2)->nullable(); 
            $table->enum('status', ['active', 'inactive'])->default('active'); 
            $table->timestamps();
            $table->softDeletes();   
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificate_fields');
    }
};
