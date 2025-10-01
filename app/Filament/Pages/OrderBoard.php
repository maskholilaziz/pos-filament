<?php

namespace App\Filament\Pages;

use App\Models\Order;
use Filament\Pages\Page;
use App\Models\OrderItem;
use App\Models\OrderNumber;

class OrderBoard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-numbered-list';
    protected static string $view = 'filament.pages.order-board';
    protected static ?string $navigationGroup = 'Manajemen Operasional';
    protected static ?string $navigationLabel = 'Papan Pesanan';

    public function getActiveOrdersProperty()
    {
        return OrderNumber::where('status', 'in_use')
            ->with([
                'orders' => function ($query) {
                    $query->whereIn('status', ['preparing', 'served'])->with([
                        'items' => function ($itemQuery) {
                            $itemQuery->orderBy('created_at', 'asc');
                        }
                    ]);
                }
            ])
            ->get()
            ->filter(fn($orderNumber) => $orderNumber->orders->isNotEmpty());
    }

    // Aksi untuk mengubah status item individual
    public function updateItemStatus(int $itemId, string $status): void
    {
        $item = OrderItem::find($itemId);
        if ($item) {
            $item->update(['status' => $status]);
        }
    }

    // Aksi untuk menandai semua pesanan telah diantar
    public function markAsCompleted(int $orderNumberId): void
    {
        $orderNumber = OrderNumber::find($orderNumberId);
        if ($orderNumber) {
            // Update semua order terkait menjadi 'completed'
            $orderNumber->orders()->where('status', 'preparing')->update(['status' => 'completed']);
            // Ubah status nomor menjadi 'available'
            $orderNumber->update(['status' => 'available']);

            // Beri notifikasi (opsional)
            \Filament\Notifications\Notification::make()
                ->title('Pesanan #' . $orderNumber->number_label . ' Selesai')
                ->success()
                ->send();
        }
    }
}
