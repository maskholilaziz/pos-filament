<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Struk Pembayaran</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            width: 280px;
            font-size: 12px;
        }

        .header,
        .footer {
            text-align: center;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table td,
        .items-table th {
            padding: 5px 0;
            border-bottom: 1px dashed #000;
        }

        .items-table .quantity,
        .items-table .price {
            text-align: right;
        }

        .total-section td {
            padding: 2px 0;
        }

        hr {
            border: none;
            border-top: 1px dashed #000;
        }
    </style>
</head>

<body onload="window.print(); setTimeout(window.close, 0);">
    <div class="header">
        <h3>** NAMA KAFE ANDA **</h3>
        <p>
            Alamat Kafe Anda<br>
            Telepon: 0812-3456-7890
        </p>
        <hr>
        <p>
            Nomor Invoice: {{ $order->invoice_number }}<br>
            Kasir: {{ $order->cashier->name ?? 'N/A' }}<br>
            Tanggal: {{ $order->created_at->format('d/m/Y H:i') }}
        </p>
    </div>
    <hr>
    <table class="items-table">
        @foreach ($order->items as $item)
            <tr>
                <td colspan="2">{{ $item->product_name }}</td>
            </tr>
            <tr>
                <td>{{ $item->quantity }} x {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td class="price">{{ number_format($item->total_price, 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </table>
    <hr>
    <table class="total-section" style="width: 100%;">
        <tr>
            <td>Total</td>
            <td class="price">{{ number_format($order->total_price, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Bayar</td>
            <td class="price">{{ number_format($order->amount_paid, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Kembali</td>
            <td class="price">{{ number_format($order->change, 0, ',', '.') }}</td>
        </tr>
    </table>
    <hr>
    <div class="footer">
        <p>Terima Kasih Atas Kunjungan Anda!</p>
    </div>
</body>

</html>
