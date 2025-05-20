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
        Schema::create('certificates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id'); 
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();

            $table->uuid('document_id');  
            $table->foreign('document_id')->references('id')->on('documents')->cascadeOnDelete()->cascadeOnUpdate();
            
            $table->string('certificate_number')->unique()->nullable();

            $table->decimal('total_score', 10, 2)->nullable();
            $table->decimal('average_score', 10, 2)->nullable();  

            $table->string('certificate_path')->nullable(); 

            $table->enum('status', ['draft', 'issued', 'revoked'])->default('issued');
            $table->timestamp('issued_at')->nullable();   
        
            $table->timestamps();
            $table->softDeletes(); 

            $table->index('user_id');
            $table->index('document_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
