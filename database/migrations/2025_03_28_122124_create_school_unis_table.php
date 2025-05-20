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
        Schema::create('school_unis', function (Blueprint $table) {
            $table->uuid('id')->primary();  
            $table->string('school_university_name', 100);
            $table->string('school_major', 100)->nullable();
            $table->string('university_faculty', 100)->nullable();
            $table->string('university_program_study', 100)->nullable();
            $table->text('school_university_address');
            $table->string('school_university_postal_code', 10);
            $table->string('school_university_province', 100);
            $table->string('school_university_city', 100);
            $table->string('school_university_district', 100);
            $table->string('school_university_village', 100);
            $table->string('school_university_phone_number', 20)->nullable();
            $table->string('school_university_email', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_unis');
    }
};
