<?php

namespace App\Support\Kesiswaan;

use App\Models\Kesiswaan\Classroom;
use App\Models\Kesiswaan\Registration;
use App\Models\Kesiswaan\RfidLog;
use App\Models\Kesiswaan\Student;
use App\Models\Kesiswaan\StudentAttendance;
use App\Models\Kesiswaan\StudentHistory;
use App\Support\AcademicYear;
use Illuminate\Support\Carbon;

/** Data ringkasan dashboard modul Kesiswaan (database unit aktif). */
class DashboardStats
{
    // Sama dengan Rekap Absensi: hadir, terlambat, dan izin pulang dihitung masuk sekolah.
    private const PRESENT = 'coalesce(sum(case when hadir = 1 or terlambat = 1 or ijin_pulang = 1 then 1 else 0 end), 0)';

    public static function make(): array
    {
        $period = AcademicYear::active();
        $today = now()->toDateString();

        /* Siswa */
        $gender = Student::query()
            ->where('aktif', true)
            ->selectRaw('a_jk, count(*) as total')
            ->groupBy('a_jk')
            ->pluck('total', 'a_jk');

        $activeStudents = (int) $gender->sum();

        $withRfid = Student::query()
            ->where('aktif', true)
            ->whereNotNull('rfid')
            ->where('rfid', '!=', '')
            ->count();

        /* Calon siswa */
        $registrationsTotal = Registration::query()->count();
        $registrationsPending = Registration::query()->where('status_daftar', false)->count();

        /* Kelas & pengelompokan (periode aktif) */
        $perClass = $period
            ? StudentHistory::query()
                ->forPeriod($period->id)
                ->selectRaw('class_id, count(*) as total')
                ->groupBy('class_id')
                ->pluck('total', 'class_id')
            : collect();

        $classes = Classroom::query()
            ->orderBy('nourut')
            ->orderBy('kelas')
            ->get()
            ->map(fn (Classroom $class) => [
                'name' => $class->kelas,
                'level' => $class->jenjang,
                'total' => (int) ($perClass[$class->id] ?? 0),
            ]);

        $grouped = (int) $perClass->sum();

        /* Absensi hari ini */
        $row = StudentAttendance::query()
            ->where('tgl_absen', $today)
            ->toBase()
            ->selectRaw(
                'count(*) as total, '.self::PRESENT.' as present, '
                .'coalesce(sum(hadir), 0) as hadir, coalesce(sum(terlambat), 0) as terlambat, '
                .'coalesce(sum(sakit), 0) as sakit, coalesce(sum(ijin_tidakmasuk), 0) as izin, '
                .'coalesce(sum(ijin_pulang), 0) as izin_pulang, coalesce(sum(alpha), 0) as alpha'
            )
            ->first();

        $attendance = [
            'total' => (int) ($row->total ?? 0),
            'present' => (int) ($row->present ?? 0),
            'status' => [
                'hadir' => (int) ($row->hadir ?? 0),
                'terlambat' => (int) ($row->terlambat ?? 0),
                'sakit' => (int) ($row->sakit ?? 0),
                'izin' => (int) ($row->izin ?? 0),
                'izin_pulang' => (int) ($row->izin_pulang ?? 0),
                'alpha' => (int) ($row->alpha ?? 0),
            ],
        ];
        $attendance['rate'] = $attendance['total'] > 0
            ? (int) round($attendance['present'] / $attendance['total'] * 100)
            : null;

        /* Tren 7 hari terakhir (hanya hari yang ada datanya) */
        $trend = StudentAttendance::query()
            ->whereBetween('tgl_absen', [now()->subDays(6)->toDateString(), $today])
            ->toBase()
            ->selectRaw('tgl_absen as day, count(*) as total, '.self::PRESENT.' as present')
            ->groupBy('tgl_absen')
            ->orderBy('tgl_absen')
            ->get()
            ->map(fn ($r) => [
                'date' => Carbon::parse($r->day),
                'rate' => $r->total > 0 ? (int) round($r->present / $r->total * 100) : 0,
            ]);

        return [
            'period' => $period,
            'students' => [
                'active' => $activeStudents,
                'male' => (int) ($gender['LAKI-LAKI'] ?? 0),
                'female' => (int) ($gender['PEREMPUAN'] ?? 0),
                'with_rfid' => $withRfid,
                'grouped' => $grouped,
                'ungrouped' => $period ? max(0, $activeStudents - $grouped) : 0,
            ],
            'registrations' => [
                'total' => $registrationsTotal,
                'pending' => $registrationsPending,
            ],
            'classes' => $classes,
            'attendance' => $attendance,
            'trend' => $trend,
            'rfid_logs' => RfidLog::query()->latest()->limit(5)->get(),
        ];
    }
}