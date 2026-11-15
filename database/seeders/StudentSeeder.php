<?php

namespace Database\Seeders;

use App\Models\Kesiswaan\Student;
use App\Models\Unit;
use App\Support\Kesiswaan\StudentFields;
use App\Support\UnitDatabase;
use Illuminate\Database\Seeder;

/** Data contoh siswa untuk datatable. */
class StudentSeeder extends Seeder
{
    // Jumlah siswa per unit.
    private const PER_UNIT = 120;

    public function run(): void
    {
        $faker = fake('id_ID');

        foreach (Unit::orderBy('id')->get() as $unit) {
            if (! UnitDatabase::exists($unit)) {
                $this->command?->warn("{$unit->code}: database belum ada, dilewati.");

                continue;
            }

            UnitDatabase::connect($unit);

            $level = $this->level($unit);
            $existing = Student::pluck('nis')->flip();
            $rows = [];

            for ($i = 1; $i <= self::PER_UNIT; $i++) {
                $nis = date('y') . $level['prefix'] . sprintf('%04d', $i);

                if ($existing->has($nis)) {
                    continue;
                }

                $rows[] = $this->makeRow($faker, $nis, $level);
            }

            foreach (array_chunk($rows, 100) as $chunk) {
                Student::insert($chunk);
            }

            $this->command?->info("{$unit->code}: " . count($rows) . ' siswa ditambahkan.');
        }
    }

    private function makeRow($faker, string $nis, array $level): array
    {
        $male = $faker->boolean(52);
        $first = $male ? $faker->firstNameMale() : $faker->firstNameFemale();
        $name = strtoupper($first . ' ' . $faker->lastName());
        $birth = $faker->dateTimeBetween("-{$level['age'][1]} years", "-{$level['age'][0]} years");
        $now = now();

        // Tahun ajaran: dominan 3 tahun terakhir.
        $years = array_keys(StudentFields::tahunAjaranOptions());
        $tahun = $faker->randomElement(array_slice($years, 0, 3));

        return [
            'nis' => $nis,
            'nisn' => $faker->numerify('00########'),
            'nik' => $faker->numerify('35##############'),
            'a_namalengkap' => $name,
            'a_namapanggilan' => strtoupper($first),
            'a_jk' => $male ? 'LAKI-LAKI' : 'PEREMPUAN',
            'a_ttl1' => $faker->randomElement(['SIDOARJO', 'SURABAYA', 'MOJOKERTO', 'GRESIK', 'MALANG']),
            'a_ttl2' => $birth->format('Y-m-d'),
            'a_agama' => 'ISLAM',
            'a_kewarganegaraan' => 'WNI',
            'a_anaknomor' => (string) $faker->numberBetween(1, 4),
            'a_statusanak' => 'Ya',
            'a_bahasa' => 'Indonesia',
            'b_alamat' => strtoupper($faker->streetAddress()),
            'b_rt' => sprintf('%03d', $faker->numberBetween(1, 12)),
            'b_rw' => sprintf('%03d', $faker->numberBetween(1, 8)),
            'b_provinsi' => 'JAWA TIMUR',
            'b_kelurahan' => strtoupper($faker->citySuffix() . ' ' . $faker->lastName()),
            'b_kecamatan' => strtoupper($faker->lastName()),
            'b_kabupaten' => $faker->randomElement(['SIDOARJO', 'MOJOKERTO', 'SURABAYA']),
            'b_kodepos' => $faker->numerify('61###'),
            'b_notelp' => $faker->numerify('08##########'),
            'email' => strtolower(preg_replace('/[^a-z0-9]/i', '', $first)) . $faker->numberBetween(1, 999) . '@example.com',
            'nik_ayah' => $faker->numerify('35##############'),
            'nik_ibu' => $faker->numerify('35##############'),
            'e_nama' => strtoupper($faker->name('male')),
            'f_nama' => strtoupper($faker->name('female')),
            'tahunajaran' => $tahun,
            'aktif' => $faker->boolean(90) ? '1' : '0',
            'tglentry' => $now->toDateString(),
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    // Prefix NIS dan rentang usia menurut jenjang unit.
    private function level(Unit $unit): array
    {
        $short = strtoupper((string) $unit->short_unit);

        if (str_contains($short, 'SMP')) {
            return ['prefix' => '03', 'age' => [12, 15]];
        }

        if (str_contains($short, 'SD')) {
            return ['prefix' => '02', 'age' => [6, 12]];
        }

        return ['prefix' => '01', 'age' => [3, 6]];
    }
}