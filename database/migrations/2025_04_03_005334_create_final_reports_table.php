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
        Schema::create('final_reports', function (Blueprint $table) {
            $table->uuid('id')->primary(); 

            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->uuid('document_id');
            $table->foreign('document_id')->references('id')->on('documents')->onDelete('cascade');

            $table->uuid('school_university_id');
            $table->foreign('school_university_id')->references('id')->on('school_unis')->onDelete('cascade');

            $table->string('title');
            $table->text('report');
            $table->string('assessment_report_file')->nullable();
            $table->string('final_report_file')->nullable();
            $table->string('photo')->nullable();
            $table->string('video')->nullable();  

            $table->uuid('mentor_verified_by_id')->nullable(); 
            $table->enum('mentor_verification_status', ['approved', 'pending', 'rejected'])->default('pending');
            $table->string('mentor_rejection_note')->nullable();

            $table->uuid('hr_verified_by_id')->nullable(); 
            $table->enum('hr_verification_status', ['approved', 'pending', 'rejected'])->default('pending');
            $table->string('hr_rejection_note')->nullable(); 

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('final_reports');
    }
};
