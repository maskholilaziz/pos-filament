<x-filament-panels::page>
    <div wire:poll.5s>
        {{-- PEMILIHAN STASIUN --}}
        <div class="mb-4 flex flex-wrap gap-2 p-2 bg-gray-100 dark:bg-gray-800 rounded-lg">
            @foreach ($stations as $station)
                <button wire:click="selectStation({{ $station->id }})" @class([
                    'px-4 py-2 text-sm font-bold rounded-md transition',
                    'bg-primary-600 text-white' => $stationId == $station->id,
                    'bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-200' =>
                        $stationId != $station->id,
                ])>
                    {{ $station->name }}
                </button>
            @endforeach
        </div>

        {{-- PAPAN PESANAN --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @forelse($this->activeOrders as $numberLabel => $items)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 flex flex-col h-full">
                    <div class="flex-grow">
                        <div class="text-center mb-4 pb-4 border-b dark:border-gray-700">
                            <h3 class="text-2xl font-bold text-primary-600 dark:text-primary-500">Nomor
                                {{ $numberLabel }}
                            </h3>
                            <p class="text-xs text-gray-500">{{ $items->first()->order->created_at->format('H:i') }}</p>
                        </div>

                        <div class="space-y-3">
                            @foreach ($items as $item)
                                <div class="p-2 rounded-md bg-gray-100 dark:bg-gray-700/50">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-grow mr-2">
                                            <p class="font-bold text-gray-800 dark:text-gray-200">
                                                {{ $item->product_name }}
                                            </p>

                                            {{-- ===== BLOK KODE BARU UNTUK MENAMPILKAN DETAIL ===== --}}
                                            @if ($item->selected_options || $item->notes)
                                                <div
                                                    class="mt-1 text-xs text-gray-500 dark:text-gray-400 pl-2 border-l-2 dark:border-gray-600">
                                                    @php
                                                        $details = [];
                                                        if ($item->selected_options) {
                                                            foreach (
                                                                $item->selected_options
                                                                as $groupId => $optionValue
                                                            ) {
                                                                $group = \App\Models\OptionGroup::find($groupId);
                                                                if (!$group) {
                                                                    continue;
                                                                }
                                                                if (is_array($optionValue)) {
                                                                    $optionNames = [];
                                                                    foreach ($optionValue as $optionId => $isSelected) {
                                                                        if ($isSelected) {
                                                                            $option = \App\Models\Option::find(
                                                                                $optionId,
                                                                            );
                                                                            if ($option) {
                                                                                $optionNames[] = $option->name;
                                                                            }
                                                                        }
                                                                    }
                                                                    if (!empty($optionNames)) {
                                                                        $details[] =
                                                                            "<b>{$group->name}:</b> " .
                                                                            implode(', ', $optionNames);
                                                                    }
                                                                } else {
                                                                    $option = \App\Models\Option::find($optionValue);
                                                                    if ($option) {
                                                                        $details[] =
                                                                            "<b>{$group->name}:</b> " . $option->name;
                                                                    }
                                                                }
                                                            }
                                                        }
                                                        if ($item->notes) {
                                                            $details[] = '<b>Catatan:</b> ' . e($item->notes);
                                                        }
                                                    @endphp
                                                    {!! implode('<br>', $details) !!}
                                                </div>
                                            @endif
                                            {{-- ================================================= --}}
                                        </div>
                                        <p class="flex-shrink-0 text-lg font-bold">x{{ $item->quantity }}</p>
                                    </div>
                                    <div class="mt-2 flex justify-end">
                                        <x-filament::button size="xs" color="success"
                                            wire:click="markItemAsReady({{ $item->id }})">
                                            Siap
                                        </x-filament::button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-12">
                    <x-heroicon-o-check-circle class="h-16 w-16 mx-auto text-gray-400" />
                    <p class="mt-4 text-gray-500">Tidak ada pesanan aktif untuk stasiun ini.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
