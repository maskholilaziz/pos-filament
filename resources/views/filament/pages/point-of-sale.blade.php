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
                        <div wire:click="addToCart({{ $product->id }})"
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
                    @forelse ($cart as $productId => $item)
                        <div class="flex items-center justify-between gap-4 rounded-lg border p-3 dark:border-gray-700">
                            <div>
                                <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $item['name'] }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ \Illuminate\Support\Number::currency($item['price'], 'IDR', 'id') }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button wire:click="updateQuantity({{ $productId }}, 'decrement')"
                                    class="rounded-md border p-1 hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-700">
                                    <x-heroicon-o-minus class="h-4 w-4 text-gray-800 dark:text-gray-200" />
                                </button>
                                <span
                                    class="w-8 text-center font-bold text-gray-800 dark:text-gray-200">{{ $item['quantity'] }}</span>
                                <button wire:click="updateQuantity({{ $productId }}, 'increment')"
                                    class="rounded-md border p-1 hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-700">
                                    <x-heroicon-o-plus class="h-4 w-4 text-gray-800 dark:text-gray-200" />
                                </button>
                                <button wire:click="removeFromCart({{ $productId }})"
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
    </div>
</x-filament-panels::page>
