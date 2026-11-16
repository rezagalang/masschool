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
        Schema::create('student_attendances', function (Blueprint $table) {
            $table->id();
            // Lintas DB pusat/unit: tanpa foreign key.
            $table->unsignedBigInteger('academic_period_id')->nullable();
            $table->string('semester', 10);
            // Satu DB: tetap pakai FK.
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->date('tgl_absen');
            $table->boolean('terlambat')->default(false);
            $table->boolean('ijin_pulang')->default(false);
            $table->boolean('sakit')->default(false);
            $table->boolean('ijin_tidakmasuk')->default(false);
            $table->boolean('alpha')->default(false);
            $table->boolean('hadir')->default(false);
            $table->string('keterangan', 50)->nullable();
            $table->timestamps();
            // 1 siswa = 1 catatan absensi per tanggal.
            $table->unique(['student_id', 'tgl_absen']);
            $table->index(['class_id', 'tgl_absen']);
            $table->index('academic_period_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_attendances');
    }
};