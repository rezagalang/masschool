<?php

namespace App\Support\Kesiswaan;

use App\Models\Kesiswaan\Student;
use Illuminate\Validation\Rule;

/** Definisi terpusat field form/detail siswa (tab, label, tipe, panjang) untuk form/show. */
class StudentFields
{
    public const AGAMA = [
        'ISLAM' => 'ISLAM',
        'KRISTEN' => 'KRISTEN',
        'HINDU' => 'HINDU',
        'BUDHA' => 'BUDHA',
        'KONGHUCU' => 'KONGHUCU',
        'KEPERCAYAAN' => 'KEPERCAYAAN',
    ];

    // Field wilayah bertingkat, aktif berurutan dari atas ke bawah (lihat WithRegionSelects).
    public const REGION_CHAIN = ['b_provinsi', 'b_kabupaten', 'b_kecamatan', 'b_kelurahan'];

    public const KEWARGANEGARAAN = [
        'WNI' => 'WNI (Warga Negara Indonesia)',
        'WNA' => 'WNA (Warga Negara Asing)',
    ];

    /** Tab & field terurut; spec: label, type, max, required, options, span, rules. */
    public static function tabs(): array
    {
        return [
            'data_diri' => [
                'label' => 'Data Diri',
                'icon' => 'user',
                'description' => 'Identitas utama siswa.',
                'fields' => [
                    'tahunajaran' => self::select('Tahun Ajaran', self::tahunAjaranOptions(), 10, ['required' => true]),
                    'nis' => self::text('NIS', 50, ['required' => true]),
                    'nik' => self::text('NIK Siswa', 16, ['required' => true, 'rules' => ['digits:16']]),
                    'a_namalengkap' => self::text('Nama Lengkap Siswa', 100, ['required' => true]),
                    'a_namapanggilan' => self::text('Nama Panggilan', 50, ['required' => true]),
                    'a_jk' => self::select('Jenis Kelamin', [
                        'LAKI-LAKI' => 'Laki-Laki',
                        'PEREMPUAN' => 'Perempuan',
                    ], 20, ['required' => true]),
                    'a_ttl1' => self::region('Kota Tempat Lahir', 50, ['required' => true]),
                    'a_ttl2' => self::date('Tanggal Lahir', ['required' => true]),
                    'a_agama' => self::select('Agama', self::AGAMA, 50, ['required' => true]),
                    'a_kewarganegaraan' => self::select('Kewarganegaraan', self::KEWARGANEGARAAN, 50, ['required' => true]),
                    'a_anaknomor' => self::text('Anak Keberapa', 50, ['required' => true]),
                    'a_kandung' => self::text('Jumlah Saudara Kandung', 50),
                    'a_tiri' => self::text('Jumlah Saudara Tiri', 50),
                    'a_angkat' => self::text('Jumlah Saudara Angkat', 50),
                    'a_yatimpiatu' => self::select('Yatim/Piatu', [
                        'Tidak' => 'Tidak',
                        'Yatim' => 'Yatim',
                        'Piatu' => 'Piatu',
                        'Yatim Piatu' => 'Yatim Piatu',
                    ], 50),
                    'a_statusanak' => self::select('Status (Anak Kandung/Bukan)', [
                        'Ya' => 'Anak Kandung',
                        'Tidak' => 'Bukan Anak Kandung',
                    ], 20, ['required' => true]),
                    'a_bahasa' => self::text('Bahasa Sehari-hari yang Dipakai', 50, ['required' => true]),
                ],
            ],

            'tempat_tinggal' => [
                'label' => 'Tempat Tinggal',
                'icon' => 'home',
                'description' => 'Alamat, kontak, dan tempat tinggal siswa saat ini.',
                'fields' => [
                    'b_alamat' => self::text('Alamat', 150, ['required' => true]),
                    'b_rt' => self::text('RT', 10, ['span' => 3]),
                    'b_rw' => self::text('RW', 10, ['span' => 3]),
                    'b_provinsi' => self::region('Provinsi', 100, ['required' => true]),
                    'b_kabupaten' => self::region('Kabupaten/Kota', 100, ['required' => true]),
                    'b_kecamatan' => self::region('Kecamatan', 100, ['required' => true]),
                    'b_kelurahan' => self::region('Desa/Kelurahan', 100, ['required' => true]),
                    'b_kodepos' => self::text('Kode Pos', 10, ['required' => true]),
                    'b_notelp' => self::text('Nomor Telepon/HP', 20, ['required' => true]),
                    'email' => self::email('Email', 50, ['required' => true]),
                    'b_tinggal' => self::select('Tinggal Dengan', [
                        'Orang Tua' => 'Orang Tua',
                        'Saudara' => 'Saudara',
                        'Kost' => 'Kost',
                        'Lain2' => 'Lain-lain',
                    ], 20),
                    'b_jarak' => self::text('Jarak ke Sekolah', 20),
                ],
            ],

            'kesehatan' => [
                'label' => 'Kesehatan',
                'icon' => 'heart',
                'description' => 'Kondisi kesehatan dan ukuran badan siswa.',
                'fields' => [
                    'c_darah' => self::select('Golongan Darah', [
                        'A' => 'A', 'B' => 'B', 'AB' => 'AB', 'O' => 'O',
                    ], 10),
                    'c_penyakit' => self::text('Penyakit yang Diderita', 50),
                    'c_kelainan' => self::text('Kelainan Jasmani', 50),
                    'c_tinggi' => self::number('Tinggi Badan (cm)', 50),
                    'c_berat' => self::number('Berat Badan (kg)', 50),
                ],
            ],

            'pendidikan' => [
                'label' => 'Pendidikan',
                'icon' => 'academic-cap',
                'description' => 'Riwayat sekolah sebelumnya.',
                'fields' => [
                    'd_lulusan' => self::text('Tamatan dari Sekolah', 100),
                    'd_lulusan_telepon' => self::text('Telepon Sekolah', 30),
                    'd_lulusan_alamat' => self::text('Alamat Sekolah', 50),
                ],
            ],

            'ayah' => [
                'label' => 'Ayah',
                'icon' => 'user-circle',
                'description' => 'Data ayah siswa.',
                'fields' => ['nik_ayah' => self::text('NIK Ayah', 16, ['required' => true, 'rules' => ['digits:16']])]
                    + self::parent('e', 'Ayah', withStatus: true),
            ],

            'ibu' => [
                'label' => 'Ibu',
                'icon' => 'user-circle',
                'description' => 'Data ibu siswa.',
                'fields' => ['nik_ibu' => self::text('NIK Ibu', 16, ['required' => true, 'rules' => ['digits:16']])]
                    + self::parent('f', 'Ibu', withStatus: true),
            ],

            'wali' => [
                'label' => 'Wali',
                'icon' => 'users',
                'description' => 'Diisi jika siswa memiliki wali.',
                'fields' => self::parent('g', 'Wali', withStatus: false),
            ],

            'lainnya' => [
                'label' => 'Hobi',
                'icon' => 'sparkles',
                'description' => 'Minat dan hobi siswa.',
                'fields' => [
                    'h_kesenian' => self::text('Kesenian', 50),
                    'h_olahraga' => self::text('Olahraga', 50),
                    'h_organisasi' => self::text('Kemasyarakatan/Organisasi', 50),
                    'h_lain' => self::text('Lain-lain', 50),
                ],
            ],
        ];
    }

    /** Semua field dalam satu array datar: nama kolom => spec. */
    public static function fields(): array
    {
        return collect(self::tabs())->flatMap(fn ($tab) => $tab['fields'])->all();
    }

    /** Nilai awal form kosong (string kosong), dengan beberapa default. */
    public static function defaults(): array
    {
        $form = array_fill_keys(array_keys(self::fields()), '');

        return array_merge($form, [
            'a_kewarganegaraan' => 'WNI',
            'e_kewarganegaraan' => 'WNI',
            'f_kewarganegaraan' => 'WNI',
            'g_kewarganegaraan' => 'WNI',
            'tahunajaran' => self::currentTahunAjaran(),
        ]);
    }

    /** Aturan validasi untuk properti $form pada komponen Livewire. */
    public static function rules(?int $ignoreId = null): array
    {
        $rules = [];

        foreach (self::fields() as $name => $spec) {
            $rule = [$spec['required'] ? 'required' : 'nullable'];

            $rule[] = match ($spec['type']) {
                'date' => 'date_format:Y-m-d',
                'email' => 'email',
                'number' => 'numeric',
                default => 'string',
            };

            // Validasi field tanggal lahir: wajib masuk akal & dalam rentang kolom DATE.
            if ($spec['type'] === 'date') {
                array_push($rule, 'after_or_equal:1900-01-01', 'before_or_equal:today');
            }

            // 'max' pada rule numeric berarti nilai maksimum, bukan panjang teks.
            if ($spec['type'] === 'number') {
                array_push($rule, 'min:0', 'max:999');
            } elseif ($spec['type'] !== 'date') {
                $rule[] = 'max:' . $spec['max'];
            }

            foreach ($spec['rules'] as $extra) {
                $rule[] = $extra;
            }

            if ($name === 'nis') {
                $rule[] = Rule::unique(Student::class, 'nis')->ignore($ignoreId);
            }

            $rules["form.{$name}"] = $rule;
        }

        return $rules;
    }

    /** Nama field yang ramah di pesan error validasi. */
    public static function attributes(): array
    {
        $attributes = [];

        foreach (self::tabs() as $key => $tab) {
            $prefix = in_array($key, ['ayah', 'ibu', 'wali'], true) ? $tab['label'] . ' - ' : '';

            foreach ($tab['fields'] as $name => $spec) {
                $attributes["form.{$name}"] = $prefix . $spec['label'];
            }
        }

        return $attributes;
    }

    /** Ubah input form jadi data siap simpan: string kosong -> null. */
    public static function normalize(array $form): array
    {
        $data = [];

        foreach (array_keys(self::fields()) as $name) {
            $value = $form[$name] ?? null;
            $value = is_string($value) ? trim($value) : $value;

            $data[$name] = ($value === '' || $value === null) ? null : (string) $value;
        }

        return $data;
    }

    /** Opsi tahun ajaran: 4 tahun lalu s.d. tahun depan, format 2026/2027. */
    public static function tahunAjaranOptions(): array
    {
        $options = [];
        $start = (int) date('Y') + 1;

        for ($year = $start; $year >= $start - 5; $year--) {
            $label = $year . '/' . ($year + 1);
            $options[$label] = $label;
        }

        return $options;
    }

    /** Tahun ajaran berjalan (Juli = awal tahun ajaran baru). */
    public static function currentTahunAjaran(): string
    {
        $year = (int) date('Y');

        if ((int) date('n') < 7) {
            $year--;
        }

        return $year . '/' . ($year + 1);
    }

    /** Field data orang tua/wali. */
    protected static function parent(string $p, string $who, bool $withStatus): array
    {
        $fields = [
            "{$p}_nama" => self::text("Nama {$who}", 100, ['required' => $p !== 'g']),
            "{$p}_ttl1" => self::region('Kota Tempat Lahir', 50),
            "{$p}_ttl2" => self::date('Tanggal Lahir'),
            "{$p}_agama" => self::select('Agama', self::AGAMA, 50),
            "{$p}_kewarganegaraan" => self::select('Kewarganegaraan', self::KEWARGANEGARAAN, 50),
            "{$p}_pendidikan" => self::text('Pendidikan', 50),
            "{$p}_pekerjaan1" => self::text('Bekerja Di', 50),
            "{$p}_pekerjaan2" => self::text('Jabatan', 50),
            "{$p}_penghasilan" => self::text('Penghasilan/Bulan', 50),
            "{$p}_alamat" => self::text('Alamat Rumah', 150),
            "{$p}_telp" => self::text('Nomor Telp/HP', $p === 'e' ? 100 : 50),
        ];

        if ($withStatus) {
            $fields["{$p}_masih"] = self::select('Masih Hidup/Meninggal', [
                'Masih Hidup' => 'Masih Hidup',
                'Meninggal Dunia' => 'Meninggal Dunia',
            ], 50);
        }

        return $fields;
    }

    protected static function spec(string $type, string $label, array $extra): array
    {
        return $extra + [
            'label' => $label,
            'type' => $type,
            'max' => 255,
            'required' => false,
            'options' => [],
            'span' => null,
            'region' => false,
            'rules' => [],
        ];
    }

    protected static function text(string $label, int $max, array $extra = []): array
    {
        return self::spec('text', $label, ['max' => $max] + $extra);
    }

    protected static function number(string $label, int $max, array $extra = []): array
    {
        return self::spec('number', $label, ['max' => $max] + $extra);
    }

    protected static function email(string $label, int $max, array $extra = []): array
    {
        return self::spec('email', $label, ['max' => $max] + $extra);
    }

    protected static function date(string $label, array $extra = []): array
    {
        return self::spec('date', $label, $extra);
    }

    protected static function select(string $label, array $options, int $max, array $extra = []): array
    {
        return self::spec('select', $label, ['options' => $options, 'max' => $max] + $extra);
    }

    // Select wilayah: opsinya dimuat dari database wilayah (bukan dari $options).
    protected static function region(string $label, int $max, array $extra = []): array
    {
        return self::select($label, [], $max, ['region' => true] + $extra);
    }
}