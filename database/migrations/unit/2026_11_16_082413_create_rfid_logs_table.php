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
        Schema::create('rfid_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_id')->nullable();
            $table->string('rfid', 30);
            $table->double('saldo')->default(0);
            $table->string('nis', 50);
            $table->string('nama', 100);
            $table->string('kelas', 50)->nullable();
            $table->unsignedBigInteger('academic_period_id')->nullable();
            $table->string('petugas', 100);
            $table->string('aksi', 20);
            $table->timestamps();

            $table->index(['student_id', 'created_at']);
            $table->index('rfid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfid_logs');
    }
};
