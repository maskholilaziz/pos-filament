<x-filament-panels::page @class(['fi-pos-page'])>
    {{-- TAB PEMILIHAN MODE --}}
    <div class="mb-6 flex items-center border-b dark:border-gray-700">
        <button wire:click="setMode('new')" @class([
            'px-4 py-3 font-semibold transition',
            'text-primary-600 border-b-2 border-primary-600' => $mode === 'new',
            'text-gray-500 hover:text-gray-700' => $mode !== 'new',
        ])>
            Pesanan Baru
        </button>
        <button wire:click="setMode('add')" @class([
            'px-4 py-3 font-semibold transition',
            'text-primary-600 border-b-2 border-primary-600' => $mode === 'add',
            'text-gray-500 hover:text-gray-700' => $mode !== 'add',
        ])>
            Tambah ke Pesanan Aktif
        </button>
    </div>

    {{-- KONTEN UTAMA --}}
    <div x-data="{ mode: @entangle('mode') }">
        {{-- Tampilan jika mode 'Tambah Pesanan' --}}
        <div x-show="mode === 'add'" class="mb-6">
            @if (!$activeOrderNumber)
                <h3 class="text-lg font-semibold mb-2 text-gray-800 dark:text-gray-200">Pilih Nomor Pesanan yang Akan
                    Ditambah</h3>
                <div class="flex flex-wrap gap-4">
                    @forelse($this->activeOrderNumbers as $number)
                        <x-filament::button wire:click="selectActiveOrderNumber({{ $number->id }})" size="xl">
                            {{ $number->number_label }}
                        </x-filament::button>
                    @empty
                        <p class="text-gray-500">Tidak ada pesanan aktif saat ini.</p>
                    @endforelse
                </div>
            @else
                {{-- Tampilan setelah nomor dipilih --}}
                <div class="p-4 bg-primary-50 dark:bg-gray-800 rounded-lg flex justify-between items-center">
                    <p class="text-lg font-bold text-primary-700 dark:text-primary-400">Menambah Pesanan untuk Nomor:
                        {{ $activeOrderNumber->number_label }}</p>
                    <x-filament::button color="gray" wire:click="setMode('add')">Ganti Nomor</x-filament::button>
                </div>
            @endif
        </div>

        {{-- Tampilan POS utama (Produk & Keranjang), hanya muncul jika mode 'new' atau nomor pesanan aktif sudah dipilih --}}
        <div x-data="{ show: @entangle('showPosInterface') }" x-show="show" x-transition>
            <div class="grid grid-cols-12 gap-6">

                {{-- Kolom Kiri - Daftar Produk & Paket --}}
                <div class="col-span-12 md:col-span-7">
                    <x-filament::card>
                        {{-- Filter Kategori --}}
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold mb-2 text-gray-800 dark:text-gray-200">Kategori</h3>
                            <div class="flex flex-wrap gap-2">
                                <div wire:click="selectCategory(null)" @class([
                                    'cursor-pointer rounded-lg px-3 py-1 text-sm font-semibold transition',
                                    'bg-primary-500 text-white' => is_null($selectedCategory),
                                    'bg-gray-200 dark:bg-gray-700 hover:bg-gray-300' => !is_null(
                                        $selectedCategory),
                                ])>
                                    Semua
                                </div>
                                @foreach ($this->categories as $category)
                                    <div wire:click="selectCategory({{ $category->id }})" @class([
                                        'cursor-pointer rounded-lg px-3 py-1 text-sm font-semibold transition',
                                        'bg-primary-500 text-white' => $selectedCategory == $category->id,
                                        'bg-gray-200 dark:bg-gray-700 hover:bg-gray-300' =>
                                            $selectedCategory != $category->id,
                                    ])>
                                        {{ $category->name }}
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Pencarian --}}
                        <div class="mb-4 border-t pt-4 dark:border-gray-700">
                            <x-filament::input.wrapper prefix-icon="heroicon-o-magnifying-glass">
                                <x-filament::input type="text" wire:model.live.debounce.300ms="search"
                                    placeholder="Cari produk atau paket..." />
                            </x-filament::input.wrapper>
                        </div>

                        {{-- ===== GRID PRODUK & PAKET DENGAN TAMPILAN BARU ===== --}}
                        <div
                            class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 max-h-[60vh] overflow-y-auto pr-2">
                            {{-- Loop untuk Paket Promo --}}
                            @foreach ($this->bundles as $bundle)
                                <div wire:click="{{ $bundle->can_be_sold ? 'selectItem(' . $bundle->id . ', \'bundle\')' : '' }}"
                                    @class([
                                        'relative flex flex-col rounded-lg border-2 border-amber-500 bg-white p-4 shadow-sm transition',
                                        'cursor-pointer hover:ring-2 hover:ring-amber-500 dark:bg-gray-800 dark:border-amber-600' =>
                                            $bundle->can_be_sold,
                                        'opacity-50 grayscale cursor-not-allowed dark:bg-gray-800/50 dark:border-gray-700' => !$bundle->can_be_sold,
                                    ])>
                                    <div class="flex-grow">
                                        <p class="font-bold text-gray-900 dark:text-gray-200">{{ $bundle->name }}</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            {{ Str::limit($bundle->description, 60) }}</p>
                                    </div>
                                    <p class="mt-3 text-sm font-extrabold text-amber-600">
                                        {{ \Illuminate\Support\Number::currency($bundle->price ?? 0, 'IDR', 'id') }}</p>
                                    <span
                                        class="absolute -top-2 -right-2 inline-flex items-center rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/10 dark:bg-amber-900 dark:text-amber-300">Paket</span>
                                </div>
                            @endforeach

                            {{-- Loop untuk Produk Biasa --}}
                            @forelse ($this->products as $product)
                                <div wire:click="{{ $product->can_be_sold ? 'selectItem(' . $product->id . ', \'product\')' : '' }}"
                                    @class([
                                        'relative flex flex-col rounded-lg border bg-white p-4 shadow-sm transition',
                                        'cursor-pointer hover:border-primary-500 hover:ring-2 hover:ring-primary-500 dark:bg-gray-800 dark:border-gray-700' =>
                                            $product->can_be_sold,
                                        'opacity-50 grayscale cursor-not-allowed dark:bg-gray-800/50 dark:border-gray-700' => !$product->can_be_sold,
                                    ])>
                                    <div class="flex-grow">
                                        <p class="font-bold text-gray-900 dark:text-gray-200">{{ $product->name }}</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            {{ Str::limit($product->description, 60) }}</p>
                                    </div>
                                    <p class="mt-3 text-sm font-extrabold text-primary-600">
                                        {{ \Illuminate\Support\Number::currency($product->selling_price ?? 0, 'IDR', 'id') }}
                                    </p>

                                    {{-- Tanda Stok Habis --}}
                                    @if (!$product->can_be_sold)
                                        <span
                                            class="absolute -top-2 -right-2 inline-flex items-center rounded-full bg-danger-100 px-2 py-1 text-xs font-medium text-danger-700 ring-1 ring-inset ring-danger-600/10 dark:bg-danger-900 dark:text-danger-300">Habis</span>
                                    @endif
                                </div>
                            @empty
                                @if (empty($this->bundles->all()))
                                    <div
                                        class="col-span-full flex h-32 flex-col items-center justify-center text-center">
                                        <x-heroicon-o-archive-box class="h-12 w-12 text-gray-400" />
                                        <p class="mt-2 text-gray-500">Produk tidak ditemukan.</p>
                                    </div>
                                @endif
                            @endforelse
                        </div>
                    </x-filament::card>
                </div>

                {{-- Kolom Kanan - Keranjang (Tidak ada perubahan di sini) --}}
                <div class="col-span-12 md:col-span-5">
                    <x-filament::card class="flex h-full flex-col">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200">Keranjang</h3>
                            @if ($cart && $cart->isNotEmpty())
                                <x-filament::button color="danger" tag="button" wire:click="clearCart" size="sm"
                                    outlined>
                                    Kosongkan
                                </x-filament::button>
                            @endif
                        </div>

                        <div class="flex-grow space-y-3 overflow-y-auto py-4 pr-2 min-h-[50vh]">
                            @if ($cart)
                                @forelse ($cart as $cartId => $item)
                                    <div
                                        class="flex items-start justify-between gap-4 rounded-lg border p-3 dark:border-gray-700">
                                        <div class="flex-grow">
                                            <p class="font-semibold text-gray-800 dark:text-gray-200">
                                                {{ $item['name'] }}
                                            </p>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ \Illuminate\Support\Number::currency($item['price'] + $item['options_price'], 'IDR', 'id') }}
                                            </p>
                                            @if (!empty($item['options_text']))
                                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                    @foreach ($item['options_text'] as $groupName => $optionName)
                                                        <span><strong>{{ $groupName }}:</strong>
                                                            {{ $optionName }}</span><br>
                                                    @endforeach
                                                </div>
                                            @endif
                                            @if (!empty($item['notes']))
                                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400 italic">
                                                    Catatan: {{ $item['notes'] }}
                                                </div>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-2 flex-shrink-0">
                                            <button wire:click="updateQuantity('{{ $cartId }}', 'decrement')"
                                                class="rounded-md border p-1 hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-700">
                                                <x-heroicon-o-minus class="h-4 w-4" />
                                            </button>
                                            <span class="w-8 text-center font-bold">{{ $item['quantity'] }}</span>
                                            <button wire:click="updateQuantity('{{ $cartId }}', 'increment')"
                                                class="rounded-md border p-1 hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-700">
                                                <x-heroicon-o-plus class="h-4 w-4" />
                                            </button>
                                            <button wire:click="removeFromCart('{{ $cartId }}')"
                                                class="ml-2 text-danger-500 hover:text-danger-700">
                                                <x-heroicon-o-trash class="h-5 w-5" />
                                            </button>
                                        </div>
                                    </div>
                                @empty
                                    <div class="flex h-full flex-col items-center justify-center text-center">
                                        <x-heroicon-o-shopping-cart class="h-16 w-16 text-gray-300" />
                                        <p class="mt-4 text-gray-500">Keranjang masih kosong.</p>
                                    </div>
                                @endforelse
                            @endif
                        </div>

                        @if ($cart && $cart->isNotEmpty())
                            <div class="mt-auto border-t pt-4 dark:border-gray-700">
                                <div
                                    class="mb-4 flex justify-between text-lg font-bold text-gray-800 dark:text-gray-200">
                                    <span>Total</span>
                                    <span>{{ \Illuminate\Support\Number::currency($total, 'IDR', 'id') }}</span>
                                </div>
                                {{ $this->getActions()['process_payment'] }}
                            </div>
                        @endif
                    </x-filament::card>
                </div>
            </div>
        </div>
    </div>

    @if ($showOptionsModal && $selectedItem)
        <div x-data="{ open: @entangle('showOptionsModal') }" x-show="open" x-on:keydown.escape.window="open = false"
            class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen">
                <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                <div x-show="open" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl transform transition-all sm:max-w-lg sm:w-full">

                    <div class="px-6 py-4">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">{{ $selectedItem->name }}</h3>
                        <p class="text-gray-600 dark:text-gray-400">
                            {{ \Illuminate\Support\Number::currency(($selectedItemType === 'product' ? $selectedItem->selling_price : $selectedItem->price) ?? 0, 'IDR', 'id') }}
                        </p>
                    </div>

                    <div class="px-6 py-4 border-t border-b dark:border-gray-700 max-h-96 overflow-y-auto">

                        {{-- ===== KONDISI @IF DITAMBAHKAN DI SINI ===== --}}
                        {{-- Tampilkan Opsi HANYA jika item adalah produk DAN memiliki opsi --}}
                        @if ($selectedItemType === 'product' && $selectedItem->optionGroups->isNotEmpty())
                            @foreach ($selectedItem->optionGroups as $group)
                                <div class="mb-4">
                                    <h4 class="font-semibold mb-2 text-gray-800 dark:text-gray-200">
                                        {{ $group->name }}
                                    </h4>
                                    @if ($group->type === 'radio')
                                        <div class="space-y-2">
                                            @foreach ($group->options as $option)
                                                <label class="flex items-center space-x-3"><input type="radio"
                                                        wire:model.live="selectedOptions.{{ $group->id }}"
                                                        value="{{ $option->id }}"
                                                        class="form-radio h-4 w-4 text-primary-600"><span
                                                        class="text-gray-700 dark:text-gray-300">{{ $option->name }}</span>
                                                    @if ($option->price > 0)
                                                        <span
                                                            class="text-sm text-gray-500">(+{{ \Illuminate\Support\Number::currency($option->price, 'IDR', 'id') }})</span>
                                                    @endif
                                                </label>
                                            @endforeach
                                        </div>
                                    @elseif($group->type === 'checkbox')
                                        <div class="space-y-2">
                                            @foreach ($group->options as $option)
                                                <label class="flex items-center space-x-3"><input type="checkbox"
                                                        wire:model.live="selectedOptions.{{ $group->id }}.{{ $option->id }}"
                                                        class="form-checkbox h-4 w-4 text-primary-600 rounded"><span
                                                        class="text-gray-700 dark:text-gray-300">{{ $option->name }}</span>
                                                    @if ($option->price > 0)
                                                        <span
                                                            class="text-sm text-gray-500">(+{{ \Illuminate\Support\Number::currency($option->price, 'IDR', 'id') }})</span>
                                                    @endif
                                                </label>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        @endif

                        {{-- Catatan Tambahan (selalu tampil) --}}
                        <div class="mt-4">
                            <label for="notes"
                                class="block font-semibold mb-2 text-gray-800 dark:text-gray-200">Catatan
                                Tambahan</label>
                            <textarea wire:model="notes" id="notes" rows="2"
                                class="w-full mt-1 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                        </div>
                    </div>

                    <div
                        class="bg-gray-50 dark:bg-gray-800/50 px-6 py-4 flex flex-row-reverse items-center justify-between">
                        <x-filament::button wire:click="addToCartFromModal">
                            Tambah ke Keranjang
                            ({{ \Illuminate\Support\Number::currency((($selectedItemType === 'product' ? $selectedItem->selling_price : $selectedItem->price) ?? 0) + $optionsTotal, 'IDR', 'id') }})
                        </x-filament::button>
                        <x-filament::button color="secondary" wire:click="closeOptionsModal">
                            Batal
                        </x-filament::button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
