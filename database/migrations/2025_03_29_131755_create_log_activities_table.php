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
        Schema::create('log_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();  
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->string('subject_type')->nullable();
            $table->uuid('subject_id')->nullable(); 
            $table->string('causer_type')->nullable();
            $table->uuid('causer_id')->nullable();  
            $table->json('properties')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('http_method')->nullable();
            $table->decimal('response_time_ms', 8, 2)->nullable();
            $table->string('url')->nullable();
            $table->enum('status', ['success', 'error'])->default('success');
            $table->timestamps(); 
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_activities');
    }
};
