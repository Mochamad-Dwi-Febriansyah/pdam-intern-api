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
        Schema::create('final_report_historis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('final_report_id');
            $table->foreign('final_report_id')->references('id')->on('final_reports')->onDelete('cascade');

            $table->uuid('updated_by');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('cascade'); 
 
            $table->string('title');
            $table->text('report');
            $table->string('assessment_report_file')->nullable();
            $table->string('final_report_file')->nullable();
            $table->string('photo')->nullable();
            $table->string('video')->nullable();  

            $table->enum('verification_type', ['mentor', 'hr']);  
            $table->enum('status', ['approved', 'pending', 'rejected']);
            $table->string('rejection_note')->nullable();

            $table->unsignedInteger('version_number')->default(1);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('final_report_historis');
    }
};
