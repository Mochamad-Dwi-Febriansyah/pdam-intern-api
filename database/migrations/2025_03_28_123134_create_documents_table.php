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
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary(); 

            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
             
            $table->uuid('school_university_id');
            $table->foreign('school_university_id')->references('id')->on('school_unis')->onDelete('cascade'); 
   
            $table->uuid('mentor_id')->nullable();   

            $table->string('registration_number', 50)->unique()->nullable();
            $table->string('identity_photo', 255);
            $table->string('application_letter', 255); 
            $table->string('accepted_letter', 255)->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('work_certificate')->nullable();
            $table->enum('document_status', ['accepted', 'pending', 'rejected'])->default('pending'); 
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
        Schema::dropIfExists('documents');
    }
};
