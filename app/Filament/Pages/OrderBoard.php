<?php

namespace App\Filament\Pages;

use App\Models\Order;
use App\Models\OrderNumber;
use Filament\Pages\Page;

class OrderBoard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-numbered-list';
    protected static string $view = 'filament.pages.order-board';
    protected static ?string $navigationGroup = 'Manajemen Operasional';
    protected static ?string $navigationLabel = 'Papan Pesanan';

    // Properti untuk me-refresh halaman secara otomatis
    protected static ?string $pollingInterval = '5s';

    public function getActiveOrdersProperty()
    {
        // Ambil semua order number yang sedang dipakai
        return OrderNumber::where('status', 'in_use')
            ->with(['orders' => function ($query) {
                $query->whereIn('status', ['preparing', 'served']);
            }, 'orders.items'])
            ->get();
    }

    // Aksi untuk menandai semua pesanan telah diantar
    public function markAsCompleted(int $orderNumberId): void
    {
        $orderNumber = OrderNumber::find($orderNumberId);
        if ($orderNumber) {
            // Update semua order terkait menjadi 'completed'
            $orderNumber->orders()->whereIn('status', ['preparing', 'served'])->update(['status' => 'completed']);
            // Ubah status nomor menjadi 'available'
            $orderNumber->update(['status' => 'available']);
        }
    }
}
