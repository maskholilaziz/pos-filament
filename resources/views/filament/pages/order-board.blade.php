<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @forelse($this->activeOrders as $orderNumber)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 flex flex-col">
                <div class="flex-grow">
                    <h3 class="text-2xl font-bold text-center text-primary-600 dark:text-primary-500">
                        Nomor {{ $orderNumber->number_label }}
                    </h3>
                    <div class="mt-4 border-t dark:border-gray-700 pt-4 space-y-2">
                        @foreach ($orderNumber->orders->flatMap->items as $item)
                            <div class="flex justify-between items-start">
                                <div class="flex-grow">
                                    <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $item->product_name }}
                                    </p>
                                    {{-- Tampilkan detail opsi dan catatan di sini jika perlu --}}
                                </div>
                                <p class="flex-shrink-0 font-bold text-gray-800 dark:text-gray-200">
                                    x{{ $item->quantity }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="mt-6">
                    <x-filament::button wire:click="markAsCompleted({{ $orderNumber->id }})" color="success"
                        class="w-full">
                        Selesai Diantar
                    </x-filament::button>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12">
                <x-heroicon-o-check-circle class="h-16 w-16 mx-auto text-gray-400" />
                <p class="mt-4 text-gray-500">Tidak ada pesanan aktif saat ini.</p>
            </div>
        @endforelse
    </div>
</x-filament-panels::page>
