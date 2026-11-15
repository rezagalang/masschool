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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('nis', 50)->unique();
            $table->string('a_namalengkap', 100);
            $table->string('a_namapanggilan', 50);
            $table->string('a_jk', 20);
            $table->string('a_ttl1', 50);
            $table->date('a_ttl2');
            $table->string('a_agama', 50);
            $table->string('a_kewarganegaraan', 50);
            $table->string('a_anaknomor', 50);
            $table->string('a_kandung', 50)->nullable();
            $table->string('a_tiri', 50)->nullable();
            $table->string('a_angkat', 50)->nullable();
            $table->string('a_yatimpiatu', 50)->nullable();
            $table->string('a_statusanak', 20);
            $table->string('a_bahasa', 50);
            $table->string('b_alamat', 150);
            $table->string('b_rt', 10)->nullable();
            $table->string('b_rw', 10)->nullable();
            $table->string('b_provinsi', 100);
            $table->string('b_kabupaten', 100);
            $table->string('b_kecamatan', 100);
            $table->string('b_kelurahan', 100);
            $table->string('b_kodepos', 10);
            $table->string('b_notelp', 20);
            $table->string('b_tinggal', 20)->nullable();
            $table->string('b_jarak', 20)->nullable();
            $table->string('c_darah', 10)->nullable();
            $table->string('c_penyakit', 50)->nullable();
            $table->string('c_kelainan', 50)->nullable();
            $table->string('c_tinggi', 20)->nullable();
            $table->string('c_berat', 20)->nullable();
            $table->string('d_lulusan', 100)->nullable();
            $table->date('d_tglijazah')->nullable();
            $table->string('d_noijazah', 50)->nullable();
            $table->date('d_tglhuasbn')->nullable();
            $table->string('d_nohuasbn', 50)->nullable();
            $table->string('d_lama', 50)->nullable();
            $table->string('d_dari', 50)->nullable();
            $table->string('d_alasan', 50)->nullable();
            $table->string('d_dikelas', 50)->nullable();
            $table->string('d_kelompok', 20)->nullable();
            $table->date('d_tglterima')->nullable();
            $table->string('d_lulusan_alamat', 50)->nullable();
            $table->string('d_lulusan_telepon', 30)->nullable();
            $table->string('e_nama', 100);
            $table->string('e_ttl1', 50)->nullable();
            $table->date('e_ttl2')->nullable();
            $table->string('e_agama', 50)->nullable();
            $table->string('e_kewarganegaraan', 50)->nullable();
            $table->string('e_pendidikan', 50)->nullable();
            $table->string('e_pekerjaan1', 50)->nullable();
            $table->string('e_pekerjaan2', 50)->nullable();
            $table->string('e_penghasilan', 50)->nullable();
            $table->string('e_alamat', 150)->nullable();
            $table->string('e_telp', 100)->nullable();
            $table->string('e_masih', 50)->nullable();
            $table->string('f_nama', 100);
            $table->string('f_ttl1', 50)->nullable();
            $table->date('f_ttl2')->nullable();
            $table->string('f_agama', 50)->nullable();
            $table->string('f_kewarganegaraan', 50)->nullable();
            $table->string('f_pendidikan', 50)->nullable();
            $table->string('f_pekerjaan1', 50)->nullable();
            $table->string('f_pekerjaan2', 50)->nullable();
            $table->string('f_penghasilan', 50)->nullable();
            $table->string('f_alamat', 150)->nullable();
            $table->string('f_telp', 50)->nullable();
            $table->string('f_masih', 50)->nullable();
            $table->string('g_nama', 100)->nullable();
            $table->string('g_ttl1', 50)->nullable();
            $table->date('g_ttl2')->nullable();
            $table->string('g_agama', 50)->nullable();
            $table->string('g_kewarganegaraan', 50)->nullable();
            $table->string('g_pendidikan', 50)->nullable();
            $table->string('g_pekerjaan1', 50)->nullable();
            $table->string('g_pekerjaan2', 50)->nullable();
            $table->string('g_penghasilan', 50)->nullable();
            $table->string('g_alamat', 150)->nullable();
            $table->string('g_telp', 50)->nullable();
            $table->string('h_kesenian', 50)->nullable();
            $table->string('h_olahraga', 50)->nullable();
            $table->string('h_organisasi', 50)->nullable();
            $table->string('h_lain', 50)->nullable();
            $table->string('i_tahun1', 50)->nullable();
            $table->string('i_tingkat1', 50)->nullable();
            $table->string('i_dari1', 50)->nullable();
            $table->string('i_tahun2', 50)->nullable();
            $table->string('i_tingkat2', 50)->nullable();
            $table->string('i_dari2', 50)->nullable();
            $table->string('i_tahun3', 50)->nullable();
            $table->string('i_tingkat3', 50)->nullable();
            $table->string('i_dari3', 30)->nullable();
            $table->date('i_tgl')->nullable();
            $table->string('i_alasan', 50)->nullable();
            $table->string('i_tamat', 50)->nullable();
            $table->string('i_ijazah', 50)->nullable();
            $table->string('i_noijazah', 50)->nullable();
            $table->string('i_nilai', 50)->nullable();
            $table->string('j_melanjutkan', 50)->nullable();
            $table->string('j_bekerja', 50)->nullable();
            $table->date('tglentry')->nullable();
            $table->string('kelas', 50)->nullable();
            $table->string('pass', 30)->nullable();
            $table->string('jumlah_saudara_di_mas', 20)->nullable();
            $table->string('nisn', 50)->nullable();
            $table->string('kode_sekolah', 10)->default('030')->nullable();
            $table->string('norek', 20)->nullable();
            $table->string('nama_nasabah', 100)->nullable();
            $table->string('nik', 16);
            $table->string('nik_ayah', 16);
            $table->string('nik_ibu', 16);
            $table->string('no_pendaftaran', 20)->nullable();
            $table->string('kelas_dasar', 20)->nullable();
            $table->boolean('aktif')->default(true);
            $table->string('d_status_mutasi', 20)->default('baru')->nullable();
            $table->date('d_tglmutasikeluar')->nullable();
            $table->string('idortu', 10)->nullable();
            $table->string('vano', 16)->nullable();
            $table->string('vano2', 16)->nullable();
            $table->string('rfid', 30)->nullable();
            $table->string('email', 50);
            $table->string('tahunajaran', 10);
            $table->unsignedBigInteger('registrasi_id')->nullable()->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};