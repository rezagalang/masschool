<?php

return [
    'kesiswaan' => [
        'label' => 'Kesiswaan',
        'icon' => 'academic-cap',
        'tone' => 'emerald',
        'description' => 'Data siswa, kelas, dan kegiatan kesiswaan.',
        'menu' => [
            [
                'label' => 'Calon Siswa',
                'icon' => 'user-plus',
                'route' => 'kesiswaan.registrations.index',
                'active' => 'kesiswaan.registrations.*',
            ],
            [
                'label' => 'Daftar Siswa',
                'icon' => 'users',
                'route' => 'kesiswaan.students.index',
                'active' => 'kesiswaan.students.*',
            ],
            [
                'label' => 'Kelas',
                'icon' => 'rectangle-stack',
                'route' => 'kesiswaan.classes.index',
                'active' => 'kesiswaan.classes.*',
            ],
            [
                'label' => 'Pengelompokan',
                'icon' => 'user-group',
                'route' => 'kesiswaan.groupings.index',
                'active' => 'kesiswaan.groupings.*',
            ],
        ],
    ],
    'keuangan' => [
        'label' => 'Keuangan',
        'icon' => 'banknotes',
        'tone' => 'sky',
        'description' => 'Tagihan, pembayaran, dan laporan keuangan.',
    ],
    'toko' => [
        'label' => 'Toko',
        'icon' => 'shopping-bag',
        'tone' => 'amber',
        'description' => 'Penjualan dan stok toko sekolah.',
    ],
];