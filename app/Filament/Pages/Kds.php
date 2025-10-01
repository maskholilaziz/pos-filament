<?php

namespace App\Filament\Pages;

use App\Models\OrderItem;
use App\Models\Station;
use Filament\Pages\Page;

class Kds extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-queue-list';
    protected static string $view = 'filament.pages.kds';
    protected static ?string $navigationGroup = 'Manajemen Operasional';
    protected static ?string $navigationLabel = 'Layar KDS';
    protected static ?string $title = 'Kitchen Display System';

    public ?int $stationId = null;
    public $stations;

    public function mount(): void
    {
        $this->stations = Station::where('output_type', 'kds')->get();
        // Pilih stasiun pertama secara default jika ada
        if ($this->stations->isNotEmpty() && is_null($this->stationId)) {
            $this->stationId = $this->stations->first()->id;
        }
    }

    public function selectStation(int $stationId): void
    {
        $this->stationId = $stationId;
    }

    public function getActiveOrdersProperty()
    {
        if (!$this->stationId) {
            return collect();
        }

        return OrderItem::where('status', 'preparing')
            ->whereHas('product', function ($query) {
                $query->where('station_id', $this->stationId);
            })
            ->with('order.orderNumber')
            ->get()
            ->groupBy('order.orderNumber.number_label') // Kelompokkan berdasarkan nomor pesanan
            ->sortKeys();
    }

    public function markItemAsReady(int $itemId): void
    {
        OrderItem::find($itemId)?->update(['status' => 'ready']);
    }
}
