<x-filament-panels::page @class(['fi-pos-page'])>
    {{-- Mengubah layout utama menjadi 2 kolom --}}
    <div class="grid grid-cols-12 gap-6">

        {{-- Kolom Kiri - Daftar Produk --}}
        <div class="col-span-12 md:col-span-7">
            <x-filament::card>
                {{-- BAGIAN BARU: FILTER KATEGORI --}}
                <div class="mb-4">
                    <h3 class="text-lg font-semibold mb-2 text-gray-800 dark:text-gray-200">Kategori</h3>
                    <div class="flex flex-wrap gap-2">
                        {{-- Tombol "All" --}}
                        <div wire:click="selectCategory(null)" @class([
                            'cursor-pointer rounded-lg px-3 py-1 text-sm font-semibold transition',
                            'bg-primary-500 text-white' => is_null($selectedCategory),
                            'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-300' => !is_null(
                                $selectedCategory),
                        ])>
                            Semua
                        </div>

                        {{-- Loop untuk setiap kategori --}}
                        @foreach ($this->categories as $category)
                            <div wire:click="selectCategory({{ $category->id }})" @class([
                                'cursor-pointer rounded-lg px-3 py-1 text-sm font-semibold transition',
                                'bg-primary-500 text-white' => $selectedCategory == $category->id,
                                'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-300' =>
                                    $selectedCategory != $category->id,
                            ])>
                                {{ $category->name }}
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Pencarian --}}
                <div class="mb-4 border-t pt-4">
                    <x-filament::input.wrapper prefix-icon="heroicon-o-magnifying-glass">
                        <x-filament::input type="text" wire:model.live.debounce.300ms="search"
                            placeholder="Cari produk berdasarkan nama..." />
                    </x-filament::input.wrapper>
                </div>

                {{-- Grid Produk --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 max-h-[60vh] overflow-y-auto pr-2">
                    @forelse ($this->products as $product)
                        <div wire:click="selectProduct({{ $product->id }})"
                            class="relative cursor-pointer rounded-lg border bg-white p-3 shadow-sm transition hover:border-primary-500 hover:ring-2 hover:ring-primary-500 dark:bg-gray-800 dark:border-gray-700">
                            <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $product->name }}</p>
                            <p class="text-sm font-bold text-primary-600">
                                {{ \Illuminate\Support\Number::currency($product->selling_price, 'IDR', 'id') }}
                            </p>
                            <span
                                class="absolute -top-2 -right-2 inline-flex items-center rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-900 dark:text-gray-300">
                                Stok: {{ $product->stock }}
                            </span>
                        </div>
                    @empty
                        <div class="col-span-full flex h-32 flex-col items-center justify-center text-center">
                            <div class="text-gray-400">
                                <x-heroicon-o-archive-box class="h-12 w-12" />
                            </div>
                            <p class="mt-2 text-gray-500">Produk tidak ditemukan.</p>
                        </div>
                    @endforelse
                </div>
            </x-filament::card>
        </div>

        {{-- Kolom Kanan - Keranjang --}}
        <div class="col-span-12 md:col-span-5">
            <x-filament::card class="flex h-full flex-col">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200">Keranjang</h3>
                    @if ($cart->isNotEmpty())
                        <x-filament::button color="danger" tag="button" wire:click="clearCart" size="sm"
                            outlined>
                            Kosongkan
                        </x-filament::button>
                    @endif
                </div>

                {{-- Daftar Item Keranjang --}}
                <div class="flex-grow space-y-3 overflow-y-auto py-4 pr-2 min-h-[50vh]">
                    @forelse ($cart as $cartId => $item)
                        <div class="flex items-start justify-between gap-4 rounded-lg border p-3 dark:border-gray-700">
                            {{-- Tampilan Item di Keranjang Diperbarui --}}
                            <div class="flex-grow">
                                <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $item['name'] }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ \Illuminate\Support\Number::currency($item['price'] + $item['options_price'], 'IDR', 'id') }}
                                </p>
                                {{-- Menampilkan Opsi yang Dipilih --}}
                                @if (!empty($item['options_text']))
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        @foreach ($item['options_text'] as $groupName => $optionName)
                                            <span><strong>{{ $groupName }}:</strong> {{ $optionName }}</span><br>
                                        @endforeach
                                    </div>
                                @endif
                                {{-- Menampilkan Catatan --}}
                                @if (!empty($item['notes']))
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400 italic">
                                        Catatan: {{ $item['notes'] }}
                                    </div>
                                @endif
                            </div>
                            {{-- Tombol Aksi Keranjang Diperbarui --}}
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
                            <div class="text-gray-300">
                                <x-heroicon-o-shopping-cart class="h-16 w-16" />
                            </div>
                            <p class="mt-4 text-gray-500">Keranjang masih kosong.</p>
                        </div>
                    @endforelse
                </div>

                {{-- Total & Aksi --}}
                @if ($cart->isNotEmpty())
                    <div class="mt-auto border-t pt-4 dark:border-gray-700">
                        <div class="mb-4 flex justify-between text-lg font-bold text-gray-800 dark:text-gray-200">
                            <span>Total</span>
                            <span>{{ \Illuminate\Support\Number::currency($total, 'IDR', 'id') }}</span>
                        </div>
                        {{ $this->getActions()['process_payment'] }}
                    </div>
                @endif
            </x-filament::card>
        </div>

        @if ($showOptionsModal && $selectedProduct)
            <div x-data="{ open: @entangle('showOptionsModal') }" x-show="open" x-on:keydown.escape.window="open = false"
                class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen">
                    {{-- Latar Belakang Modal --}}
                    <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                        class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

                    {{-- Konten Modal --}}
                    <div x-show="open" x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl transform transition-all sm:max-w-lg sm:w-full">

                        <div class="px-6 py-4">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                                {{ $selectedProduct->name }}</h3>
                            <p class="text-gray-600 dark:text-gray-400">
                                {{ \Illuminate\Support\Number::currency($selectedProduct->selling_price, 'IDR', 'id') }}
                            </p>
                        </div>

                        <div class="px-6 py-4 border-t border-b dark:border-gray-700 max-h-96 overflow-y-auto">
                            @foreach ($selectedProduct->optionGroups as $group)
                                <div class="mb-4">
                                    <h4 class="font-semibold mb-2 text-gray-800 dark:text-gray-200">{{ $group->name }}
                                    </h4>
                                    @if ($group->type === 'radio')
                                        <div class="space-y-2">
                                            @foreach ($group->options as $option)
                                                <label class="flex items-center space-x-3">
                                                    <input type="radio"
                                                        wire:model.live="selectedOptions.{{ $group->id }}"
                                                        value="{{ $option->id }}"
                                                        class="form-radio h-4 w-4 text-primary-600">
                                                    <span
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
                                                <label class="flex items-center space-x-3">
                                                    <input type="checkbox"
                                                        wire:model.live="selectedOptions.{{ $group->id }}.{{ $option->id }}"
                                                        class="form-checkbox h-4 w-4 text-primary-600 rounded">
                                                    <span
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

                            {{-- Catatan Tambahan --}}
                            <div class="mt-4">
                                <label for="notes"
                                    class="font-semibold mb-2 text-gray-800 dark:text-gray-200">Catatan Tambahan</label>
                                <textarea wire:model="notes" id="notes" rows="2"
                                    class="w-full mt-1 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                            </div>
                        </div>

                        <div
                            class="bg-gray-50 dark:bg-gray-800/50 px-6 py-4 sm:flex sm:flex-row-reverse items-center justify-between">
                            <x-filament::button wire:click="addToCartFromModal">
                                Tambah ke Keranjang
                                ({{ \Illuminate\Support\Number::currency($selectedProduct->selling_price + $optionsTotal, 'IDR', 'id') }})
                            </x-filament::button>
                            <x-filament::button color="secondary" wire:click="closeOptionsModal">
                                Batal
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
