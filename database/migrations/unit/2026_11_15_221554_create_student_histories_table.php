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
        Schema::create('student_histories', function (Blueprint $table) {
            $table->id();
            // Lintas DB (pusat/unit): tanpa foreign key, cukup kolom + index. 
            // Validasi via Rule::exists di aplikasi.
            $table->unsignedBigInteger('academic_period_id');
            // Satu DB (students & classes): tetap pakai FK.
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->timestamps();

            // 1 siswa = 1 kelas per periode akademik.
            $table->unique(['student_id', 'academic_period_id']);
            $table->index(['academic_period_id', 'class_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_histories');
    }
};