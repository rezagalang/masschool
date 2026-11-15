<?php

namespace App\Livewire\Concerns;

use App\Support\Kesiswaan\StudentFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\Province;

/** Dropdown wilayah bertingkat (simpan NAMA) untuk $form berdasarkan StudentFields::REGION_CHAIN. */
trait WithRegionSelects
{
    // Relasi dari model induk ke anak, urut per level (level 1 = kabupaten/kota, dst).
    private const REGION_RELATIONS = ['cities', 'districts', 'villages'];

    // Field select kota mandiri (bukan rantai alamat) untuk tempat lahir siswa, ayah, ibu, & wali.
    private const BIRTH_CITY_FIELDS = ['a_ttl1', 'e_ttl1', 'f_ttl1', 'g_ttl1'];

    // Cache per request supaya render beberapa dropdown tidak mengulang query.
    protected array $regionMemo = [];

    /** Opsi dropdown untuk satu field wilayah: [nama => nama]. */
    public function regionOptions(string $field): array
    {
        return $this->regionMemo['options'][$field] ??= $this->loadRegionOptions($field);
    }

    /** Dropdown aktif hanya jika level di atasnya sudah dipilih. */
    public function regionReady(string $field): bool
    {
        $level = array_search($field, StudentFields::REGION_CHAIN, true);

        if ($level === false || $level === 0) {
            return true;
        }

        return trim((string) ($this->form[StudentFields::REGION_CHAIN[$level - 1]] ?? '')) !== '';
    }

    /** Kosongkan level di bawah field yang baru diubah. Panggil dari updated(). */
    public function resetRegionChildren(string $name): void
    {
        if (! str_starts_with($name, 'form.')) {
            return;
        }

        $chain = StudentFields::REGION_CHAIN;
        $level = array_search(substr($name, 5), $chain, true);

        if ($level === false) {
            return;
        }

        foreach (array_slice($chain, $level + 1) as $child) {
            $this->form[$child] = '';
            $this->resetErrorBag("form.{$child}");
        }

        $this->regionMemo = [];
    }

    protected function loadRegionOptions(string $field): array
    {
        // Kota Tempat Lahir: semua kota/kabupaten, tidak bergantung pada alamat.
        if (in_array($field, self::BIRTH_CITY_FIELDS, true)) {
            return $this->regionMemo['cities'] ??= City::query()->orderBy('name')->pluck('name', 'name')->all();
        }

        $chain = StudentFields::REGION_CHAIN;
        $level = array_search($field, $chain, true);

        if ($level === false) {
            return [];
        }

        $query = $level === 0
            ? Province::query()
            : $this->regionModel($chain[$level - 1])?->{self::REGION_RELATIONS[$level - 1]}();

        if (! $query instanceof Relation && ! $query instanceof \Illuminate\Database\Eloquent\Builder) {
            return [];
        }

        return $query->orderBy('name')->pluck('name', 'name')->all();
    }

    /** Model wilayah dari nama yang dipilih di field ini (menelusuri dari provinsi). */
    protected function regionModel(string $field): ?Model
    {
        if (array_key_exists($field, $this->regionMemo['models'] ?? [])) {
            return $this->regionMemo['models'][$field];
        }

        $chain = StudentFields::REGION_CHAIN;
        $level = array_search($field, $chain, true);
        $name = trim((string) ($this->form[$field] ?? ''));
        $model = null;

        if ($level !== false && $name !== '') {
            if ($level === 0) {
                $model = Province::query()->where('name', $name)->first();
            } else {
                $model = $this->regionModel($chain[$level - 1])
                    ?->{self::REGION_RELATIONS[$level - 1]}()
                    ->where('name', $name)
                    ->first();
            }
        }

        return $this->regionMemo['models'][$field] = $model;
    }
}