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
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('unit', 50);
            $table->string('code', 20)->nullable();
            $table->string('pic', 100)->nullable(); 
            $table->string('name', 100)->nullable();
            $table->string('address')->nullable();
            $table->string('city', 50)->nullable();
            $table->string('short_unit', 10)->nullable();
            $table->string('headmaster', 100)->nullable();
            $table->string('finance', 100)->nullable();
            $table->string('chairman', 100)->nullable();
            $table->string('email_setting', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
