@props([
    'table',                // komponen Livewire ($this)
    'header' => true,       // tampilkan judul + tombol header
    'flash' => true,        // tampilkan session('status') sebagai toast
    'selectable' => true,   // kolom checkbox + bulk bar
    'exportable' => true,   // tombol export
    'loadingTargets' => 'search,perPage,sort,gotoPage,nextPage,previousPage,resetFilters,bulkDelete,deleteRow',
])

@php
    $ui = $table->ui;
    $entity = $ui['entity'];
    $filters = $ui['filters'];
    $head = $ui['header'];

    $rows = $table->rows;
    $columns = $table->displayColumns;
    $hideable = $table->hideableColumns;
    [$sortKey, $sortDir] = $table->sortState;

    $paginated = $rows instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator;
    $total = $table->total;
    $selectedCount = $table->selectedCount;
    $canDelete = $table->canDelete;
    $canBulkDelete = $selectable && $canDelete;

    $pageIds = $table->pageIds;
    $pageSelected = $pageIds !== [] && ($table->selectAll || array_diff($pageIds, $table->selected) === []);
    $partial = ! $pageSelected && $selectedCount > 0;

    $isFiltered = $table->search !== ''
        || collect(array_keys($filters))->contains(fn ($prop) => (string) $table->{$prop} !== '');

    $fmt = fn ($n) => number_format($n, 0, ',', '.');
@endphp

<div class="flex w-full flex-1 flex-col gap-6">
    <!-- Header halaman -->
    @if ($header && ! empty($head['title']))
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="xl" level="1">{{ $head['title'] }}</flux:heading>
                @if (! empty($head['subtitle']))
                    <flux:text>{{ $head['subtitle'] }}</flux:text>
                @endif
            </div>

            @if (! empty($head['actions']))
                <div class="flex flex-wrap items-center gap-2">
                    @foreach ($head['actions'] as $action)
                        @if (! empty($action['click']))
                            <flux:button :variant="$action['variant'] ?? 'filled'" :icon="$action['icon'] ?? null"
                                wire:click="{{ $action['click'] }}">
                                {{ $action['label'] }}
                            </flux:button>
                        @else
                            <flux:button :variant="$action['variant'] ?? 'filled'" :icon="$action['icon'] ?? null"
                                :href="$action['href'] ?? null" wire:navigate>
                                {{ $action['label'] }}
                            </flux:button>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    <!-- Notifikasi setelah simpan/ubah: toast Flux (flash session dari halaman sebelumnya) -->
    @if ($flash && session('status'))
        <div x-data x-init="$nextTick(() => $flux.toast({ text: @js(session('status')), variant: 'success' }))" class="hidden">
        </div>
    @endif

    <div class="relative">
        <div
            class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-white/10 dark:bg-zinc-900">

            <!-- Indikator loading tipis -->
            <div wire:loading.flex wire:target="{{ $loadingTargets }}"
                class="absolute inset-x-0 top-0 z-10 h-0.5 overflow-hidden bg-zinc-100 dark:bg-white/10">
                <div class="h-full w-1/3 animate-pulse bg-zinc-900 dark:bg-white"></div>
            </div>

            <!-- Toolbar -->
            <div
                class="flex flex-col gap-3 border-b border-zinc-200 p-3 sm:p-4 lg:flex-row lg:items-center lg:justify-between dark:border-white/10">
                <div class="flex flex-1 flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
                    <div class="w-full sm:w-72">
                        <flux:input size="sm" wire:model.live.debounce.300ms="search" icon="magnifying-glass" clearable
                            placeholder="{{ $ui['placeholder'] }}" />
                    </div>

                    @foreach ($filters as $prop => $filter)
                        <flux:select size="sm" wire:model.live="{{ $prop }}" class="sm:max-w-44" wire:key="dt-filter-{{ $prop }}">
                            <flux:select.option value="">{{ $filter['label'] }}</flux:select.option>
                            @foreach ($filter['options'] as $value => $label)
                                <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    @endforeach

                    @if ($isFiltered)
                        <flux:button size="sm" variant="subtle" icon="x-mark" wire:click="resetFilters">
                            {{ __('Reset') }}
                        </flux:button>
                    @endif
                </div>

                <div class="flex items-center justify-between gap-3 lg:justify-end">
                    @if ($hideable !== [])
                        <flux:dropdown align="end">
                            <flux:button size="sm" icon="view-columns" icon:trailing="chevron-down">
                                {{ __('Kolom') }}
                            </flux:button>

                            <flux:menu>
                                <flux:menu.group :heading="__('Tampilkan kolom')">
                                    <flux:menu.checkbox.group wire:model.live="visibleColumns">
                                        @foreach ($hideable as $key => $label)
                                            <flux:menu.checkbox value="{{ $key }}" keep-open>{{ $label }}</flux:menu.checkbox>
                                        @endforeach
                                    </flux:menu.checkbox.group>
                                </flux:menu.group>
                            </flux:menu>
                        </flux:dropdown>
                    @endif

                    @if ($exportable)
                        <flux:dropdown align="end">
                            <flux:button size="sm" icon="arrow-down-tray" icon:trailing="chevron-down"
                                wire:loading.attr="disabled" wire:target="export">
                                {{ __('Ekspor') }}
                            </flux:button>

                            <flux:menu>
                                <flux:menu.group
                                    :heading="$selectedCount > 0
                                        ? $fmt($selectedCount) . ' ' . __('data terpilih')
                                        : __('Semua hasil') . ' (' . $fmt($total) . ')'">
                                    <flux:menu.item icon="document-text" wire:click="export('csv')">CSV</flux:menu.item>
                                    <flux:menu.item icon="table-cells" wire:click="export('xlsx')">Excel (.xlsx)</flux:menu.item>
                                    <flux:menu.item icon="document" wire:click="export('pdf')">PDF</flux:menu.item>
                                </flux:menu.group>
                            </flux:menu>
                        </flux:dropdown>
                    @endif
                </div>
            </div>

            <!-- Tabel -->
            @if ($rows->isEmpty())
                <div class="flex flex-col items-center gap-3 px-6 py-16 text-center">
                    <div class="flex size-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-white/10">
                        <flux:icon :name="$isFiltered ? 'magnifying-glass' : 'inbox'" class="size-6 text-zinc-400" />
                    </div>
                    <div>
                        <flux:heading>
                            {{ $isFiltered ? __('Tidak ada :entity yang cocok', ['entity' => $entity]) : __('Belum ada data :entity', ['entity' => $entity]) }}
                        </flux:heading>
                        <flux:text class="mt-1">
                            {{ $isFiltered ? __('Coba ubah kata kunci atau reset filter.') : __('Data akan tampil di sini setelah ditambahkan.') }}
                        </flux:text>
                    </div>
                    @if ($isFiltered)
                        <flux:button size="sm" icon="x-mark" wire:click="resetFilters">{{ __('Reset filter') }}</flux:button>
                    @endif
                </div>
            @else
                <div wire:loading.class="opacity-60" wire:target="{{ $loadingTargets }}"
                    class="overflow-x-auto px-3 transition-opacity sm:px-4
                        [&_thead_th]:text-xs [&_thead_th]:font-semibold [&_thead_th]:uppercase [&_thead_th]:tracking-wide
                        [&_tbody_td]:py-3">
                    <flux:table>
                        <flux:table.columns>
                            @if ($selectable)
                                <flux:table.column class="w-10">
                                    <flux:checkbox :checked="$pageSelected" :indeterminate="$partial" wire:click="togglePage"
                                        aria-label="{{ __('Pilih semua di halaman ini') }}" />
                                </flux:table.column>
                            @endif

                            @foreach ($columns as $key => $column)
                                @if ($column['sortable'] ?? false)
                                    @php
                                        $active = $sortKey === $key;
                                    @endphp
                                    <flux:table.column :align="$column['align'] ?? 'start'">
                                        <button type="button" wire:click="sort('{{ $key }}')"
                                            class="group inline-flex items-center gap-1 uppercase tracking-wide transition-colors hover:text-zinc-900 dark:hover:text-white {{ $active ? 'text-zinc-900 dark:text-white' : '' }}">
                                            {{ $column['label'] }}
                                            <flux:icon :name="$active ? ($sortDir === 'asc' ? 'chevron-up' : 'chevron-down') : 'chevron-up-down'"
                                                variant="micro"
                                                class="size-3.5 {{ $active ? '' : 'text-zinc-400 group-hover:text-zinc-600 dark:group-hover:text-zinc-300' }}" />
                                        </button>
                                    </flux:table.column>
                                @else
                                    <flux:table.column :align="$column['align'] ?? 'start'">{{ $column['label'] }}</flux:table.column>
                                @endif
                            @endforeach
                        </flux:table.columns>

                        <flux:table.rows>
                            @if ($slot->isNotEmpty())
                                {{ $slot }}
                            @else
                                @foreach ($rows as $row)
                                    @php
                                        $checked = $table->selectAll || in_array((string) $row->getKey(), $table->selected, true);
                                    @endphp

                                    <flux:table.row :key="$row->getKey()"
                                        class="{{ $checked ? 'bg-zinc-50 dark:bg-white/5' : 'hover:bg-zinc-50/60 dark:hover:bg-white/[0.03]' }}">
                                        @if ($selectable)
                                            <flux:table.cell>
                                                @if ($table->selectAll)
                                                    <flux:checkbox checked disabled />
                                                @else
                                                    <flux:checkbox wire:model.live="selected" value="{{ $row->getKey() }}" />
                                                @endif
                                            </flux:table.cell>
                                        @endif

                                        @foreach ($columns as $key => $column)
                                                        @php
                                                $type = $column['type'] ?? 'text';
                                                $value = ($column['value'] ?? null) instanceof \Closure
                                                    ? ($column['value'])($row)
                                                    : data_get($row, $key);
                                            @endphp

                                            <flux:table.cell :align="$column['align'] ?? 'start'"
                                                class="{{ in_array($type, ['mono', 'muted']) ? 'whitespace-nowrap' : '' }}">
                                                @switch($type)
                                                    @case('avatar')
                                                        @php
                                                            $sub = $column['subtitle'] ?? null;
                                                            $sub = $sub instanceof \Closure ? $sub($row) : ($sub ? data_get($row, $sub) : null);
                                                        @endphp
                                                        <div class="flex items-center gap-3">
                                                            <flux:avatar circle size="sm" :name="(string) ($value ?: $row->getKey())"
                                                                color="auto" :color:seed="$row->getKey()" />
                                                            <div class="min-w-0">
                                                                <div class="truncate font-medium text-zinc-800 dark:text-white">{{ $value ?: '-' }}</div>
                                                                @if ($sub)
                                                                    <div class="truncate text-xs text-zinc-500">{{ $sub }}</div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        @break

                                                    @case('badge')
                                                        @php
                                                            $badgeFn = $column['badge'] ?? null;
                                                            $badge = $badgeFn ? $badgeFn($row) : null;
                                                        @endphp
                                                        @if ($badge)
                                                            <flux:badge size="sm" variant="pill" inset="top bottom"
                                                                :color="$badge['color'] ?? 'zinc'">
                                                                @if ($badge['dot'] ?? false)
                                                                    <span class="mr-1 inline-block size-1.5 rounded-full bg-current"></span>
                                                                @endif
                                                                {{ $badge['label'] }}
                                                            </flux:badge>
                                                        @else
                                                            <span class="text-zinc-400">-</span>
                                                        @endif
                                                        @break

                                                    @case('actions')
                                                        @php
                                                            $itemsFn = $column['actions'] ?? null;
                                                            $items = $itemsFn ? $itemsFn($row) : [];
                                                        @endphp
                                                        @if ($items)
                                                            <flux:dropdown align="end" position="bottom">
                                                                <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal"
                                                                    inset="top bottom" aria-label="{{ __('Aksi') }}" />

                                                                <flux:menu>
                                                                    @foreach ($items as $item)
                                                                        @if ($item['separator'] ?? false)
                                                                            <flux:menu.separator />
                                                                        @elseif ($item['delete'] ?? false)
                                                                            @if ($canDelete)
                                                                                <flux:menu.item icon="trash" variant="danger"
                                                                                    wire:click="confirmDelete('{{ addslashes((string) $row->getKey()) }}')">
                                                                                    {{ $item['label'] ?? __('Hapus') }}
                                                                                </flux:menu.item>
                                                                            @endif
                                                                        @elseif (isset($item['href']))
                                                                            <flux:menu.item :icon="$item['icon'] ?? null" :href="$item['href']" wire:navigate>
                                                                                {{ $item['label'] }}
                                                                            </flux:menu.item>
                                                                        @else
                                                                            <flux:menu.item :icon="$item['icon'] ?? null" wire:click="{{ $item['click'] }}">
                                                                                {{ $item['label'] }}
                                                                            </flux:menu.item>
                                                                        @endif
                                                                    @endforeach
                                                                </flux:menu>
                                                            </flux:dropdown>
                                                        @endif
                                                        @break

                                                    @case('mono')
                                                        <span class="font-mono text-xs text-zinc-500">{{ $value ?? '-' }}</span>
                                                        @break

                                                    @case('muted')
                                                        <span class="text-zinc-500">{{ $value ?? '-' }}</span>
                                                        @break

                                                    @default
                                                        {{ $value ?? '-' }}
                                                @endswitch
                                            </flux:table.cell>
                                        @endforeach
                                    </flux:table.row>
                                @endforeach
                            @endif
                        </flux:table.rows>
                    </flux:table>
                </div>

                <!-- Footer: show N + info + pagination -->
                <div
                    class="flex flex-col items-center gap-3 border-t border-zinc-200 bg-zinc-50/60 px-3 py-3 sm:flex-row sm:justify-between sm:px-4 dark:border-white/10 dark:bg-white/[0.02]">
                    <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-2 text-sm text-zinc-500">
                        <div class="flex items-center gap-2">
                            <span>{{ __('Tampilkan') }}</span>
                            <flux:select wire:model.live="perPage" size="sm" class="w-24">
                                @foreach ($table->perPageOptions as $option)
                                    <flux:select.option value="{{ $option }}">{{ $option }}</flux:select.option>
                                @endforeach
                                <flux:select.option value="all">{{ __('Semua') }}</flux:select.option>
                            </flux:select>
                        </div>

                        <span class="tabular-nums">
                            @if ($paginated)
                                {{ $fmt($rows->firstItem() ?? 0) }}–{{ $fmt($rows->lastItem() ?? 0) }}
                                {{ __('dari') }} {{ $fmt($total) }}
                            @else
                                {{ $fmt($rows->count()) }}
                                @if ($total > $rows->count())
                                    {{ __('dari') }} {{ $fmt($total) }}
                                @endif
                                {{ __('data') }}
                            @endif
                        </span>
                    </div>

                    @if ($paginated && $rows->lastPage() > 1)
                        @php
                            $current = $rows->currentPage();
                            $last = $rows->lastPage();
                            $pages = collect([1, $last, $current - 1, $current, $current + 1])
                                ->filter(fn ($p) => $p >= 1 && $p <= $last)
                                ->unique()->sort()->values();
                        @endphp

                        <div class="flex items-center gap-1">
                            <flux:button size="sm" variant="ghost" icon="chevron-double-left" wire:click="gotoPage(1)"
                                :disabled="$rows->onFirstPage()" aria-label="{{ __('Halaman pertama') }}" />

                            <flux:button size="sm" variant="ghost" icon="chevron-left" wire:click="previousPage"
                                :disabled="$rows->onFirstPage()" aria-label="{{ __('Sebelumnya') }}" />

                            @foreach ($pages as $i => $p)
                                @if ($i > 0 && $p - $pages[$i - 1] > 1)
                                    <span class="px-1 text-zinc-400">…</span>
                                @endif

                                <flux:button size="sm" :variant="$p === $current ? 'primary' : 'ghost'"
                                    class="min-w-8 tabular-nums" wire:click="gotoPage({{ $p }})"
                                    wire:key="dt-page-{{ $p }}">{{ $p }}</flux:button>
                            @endforeach

                            <flux:button size="sm" variant="ghost" icon="chevron-right" wire:click="nextPage"
                                :disabled="! $rows->hasMorePages()" aria-label="{{ __('Berikutnya') }}" />

                            <flux:button size="sm" variant="ghost" icon="chevron-double-right"
                                wire:click="gotoPage({{ $last }})" :disabled="! $rows->hasMorePages()"
                                aria-label="{{ __('Halaman terakhir') }}" />
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- Bulk bar melayang -->
        @if ($selectable && $selectedCount > 0)
            <div x-data="{ show: false }" x-init="$nextTick(() => show = true)" x-show="show"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-y-3 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
                class="fixed inset-x-4 bottom-6 z-30 mx-auto flex w-fit max-w-full flex-wrap items-center gap-2 rounded-xl border border-zinc-200 bg-white px-3 py-2 shadow-lg dark:border-white/15 dark:bg-zinc-800">
                <span class="px-1 text-sm font-medium tabular-nums text-zinc-800 dark:text-white">
                    {{ $fmt($selectedCount) }} {{ __('dipilih') }}
                </span>

                @if (! $table->selectAll && $paginated && $pageSelected && $total > $selectedCount)
                    <flux:button size="xs" variant="subtle" wire:click="selectAllResults">
                        {{ __('Pilih semua :total', ['total' => $fmt($total)]) }}
                    </flux:button>
                @endif

                <flux:separator vertical class="mx-1 my-1" />

                <flux:button size="xs" variant="ghost" wire:click="clearSelection">{{ __('Batal') }}</flux:button>

                @if ($canBulkDelete)
                    <flux:button size="xs" variant="danger" icon="trash" wire:click="confirmBulkDelete">
                        {{ __('Hapus') }}
                    </flux:button>
                @endif
            </div>
        @endif
    </div>

    <!-- Konfirmasi hapus satu data -->
    @if ($canDelete)
        <flux:modal name="datatable-delete" class="min-w-[22rem]">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('Hapus data?') }}</flux:heading>
                    <flux:text class="mt-2">
                        <strong>{{ $table->deleteLabel }}</strong>
                        {{ __('akan dihapus permanen dan tidak bisa dikembalikan.') }}
                    </flux:text>
                </div>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('Batal') }}</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" wire:click="deleteRow">{{ __('Hapus') }}</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    <!-- Konfirmasi bulk delete -->
    @if ($canBulkDelete)
        <flux:modal name="datatable-bulk-delete" class="min-w-[22rem]">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('Hapus :count :entity?', ['count' => $fmt($selectedCount), 'entity' => $entity]) }}</flux:heading>
                    <flux:text class="mt-2">{{ __('Data akan dihapus permanen dan tidak bisa dikembalikan.') }}</flux:text>
                </div>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('Batal') }}</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" wire:click="bulkDelete">{{ __('Hapus') }}</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>