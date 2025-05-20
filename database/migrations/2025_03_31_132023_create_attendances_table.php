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
        Schema::create('attendances', function (Blueprint $table) {
            $table->uuid('id')->primary(); 

            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('user_id'); 

            $table->date('date')->nullable();
            $table->time('check_in_time')->nullable();
            $table->string('check_in_photo', 255)->nullable(); 
            $table->time('check_out_time')->nullable();
            $table->string('check_out_photo', 255)->nullable(); 
            $table->decimal('check_in_latitude', 10, 6)->nullable();
            $table->decimal('check_out_latitude', 10, 6)->nullable();
            $table->decimal('check_in_longitude', 10, 6)->nullable(); 
            $table->decimal('check_out_longitude', 10, 6)->nullable(); 
            $table->enum('status', ['present', 'permission', 'sick', 'absent'])->nullable(); 

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
