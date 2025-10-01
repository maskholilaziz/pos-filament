<?php

namespace App\Events;

use App\Models\Order;
use App\Models\Station;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class PrintKitchenTicket
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Order $order;
    public Station $station;
    public Collection $items;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order, Station $station, Collection $items)
    {
        $this->order = $order;
        $this->station = $station;
        $this->items = $items;
    }
}
