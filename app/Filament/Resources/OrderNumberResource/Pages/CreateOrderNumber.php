<?php

namespace App\Filament\Resources\OrderNumberResource\Pages;

use App\Filament\Resources\OrderNumberResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOrderNumber extends CreateRecord
{
    protected static string $resource = OrderNumberResource::class;
}
