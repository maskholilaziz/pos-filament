<?php

namespace App\Listeners;

use App\Events\PrintKitchenTicket;
use Barryvdh\DomPDF\Facade\Pdf; // <-- Tambahkan ini
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Storage; // <-- Tambahkan ini

class SendPrintJobToPrinter
{
    /**
     * Handle the event.
     */
    public function handle(PrintKitchenTicket $event): void
    {
        // 1. Ambil data dari event
        $order = $event->order;
        $station = $event->station;
        $items = $event->items;
        $orderNumber = $order->orderNumber->number_label ?? 'takeaway';

        // 2. Buat PDF dari view
        $pdf = Pdf::loadView('print.kitchen-ticket', [
            'order' => $order,
            'station' => $station,
            'items' => $items,
        ]);

        // Atur ukuran kertas agar sesuai dengan printer thermal (80mm)
        // 226.77pt adalah ~80mm
        $pdf->setPaper([0, 0, 226.77, 841.89], 'portrait');

        // 3. Simpan PDF ke storage
        $filename = "ticket_dapur_{$station->name}_order_{$orderNumber}_" . time() . ".pdf";

        // Simpan di storage/app/public/tickets
        Storage::put("public/tickets/{$filename}", $pdf->output());

        // Di dunia nyata, Anda akan mengirim file ini ke print server.
        // Contoh: Http::post('http://192.168.1.100:9999/print', ['file' => Storage::url("tickets/{$filename}")]);

        \Illuminate\Support\Facades\Log::info("PDF Tiket Dapur telah dibuat: {$filename}");
    }
}
