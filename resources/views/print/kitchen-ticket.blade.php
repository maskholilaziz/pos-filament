<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Ticket</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            width: 280px;
            /* Lebar kertas thermal 80mm */
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

        .items-table .quantity {
            text-align: center;
        }

        .notes {
            font-style: italic;
            font-size: 11px;
            padding-left: 10px;
        }

        hr {
            border: none;
            border-top: 1px dashed #000;
        }
    </style>
</head>

<body>
    <div class="header">
        <h3>** TIKET DAPUR **</h3>
        <p>
            <strong>Nomor Pesanan: {{ $order->orderNumber->number_label ?? '' }}</strong><br>
            Stasiun: {{ $station->name }}<br>
            Waktu: {{ $order->created_at->format('d/m/Y H:i') }}
        </p>
    </div>
    <hr>
    <table class="items-table">
        @foreach ($items as $item)
            <tr>
                <td>
                    <strong>{{ $item->product_name }}</strong>
                    {{-- Logika untuk menampilkan opsi --}}
                    @if ($item->selected_options)
                        <div class="notes">
                            @foreach ($item->selected_options as $option)
                                @if (is_array($option))
                                    {{ implode(', ', $option) }}
                                @else
                                    {{ $option }}
                                @endif
                            @endforeach
                        </div>
                    @endif
                    @if ($item->notes)
                        <div class="notes">Catatan: {{ $item->notes }}</div>
                    @endif
                </td>
                <td class="quantity">x{{ $item->quantity }}</td>
            </tr>
        @endforeach
    </table>
    <hr>
    <div class="footer">
        <p>{{ $order->customer_name }}</p>
    </div>
</body>

</html>
