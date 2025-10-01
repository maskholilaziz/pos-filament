<?php

namespace App\Http\Controllers;

use App\Models\Order;

class PrintController extends Controller
{
    public function receipt(Order $order)
    {
        return view('print.customer-receipt', compact('order'));
    }
}
