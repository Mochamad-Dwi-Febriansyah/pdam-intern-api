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
        Schema::create('daily_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
 
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
         
            $table->uuid('attendance_id');
            $table->foreign('attendance_id')->references('id')->on('attendances')->onDelete('cascade');
        
            $table->string('title')->nullable();  
            $table->text('report')->nullable();  
            $table->text('result')->nullable();  
         
            $table->enum('status', ['approved', 'pending', 'rejected'])->default('pending');
             
            $table->string('rejection_note')->nullable();
         
            $table->uuid('verified_by_id')->nullable(); 
         
            $table->timestamps();
             
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_reports');
    }
};
