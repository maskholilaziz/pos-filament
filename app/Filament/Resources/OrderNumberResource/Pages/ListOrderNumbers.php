<?php

namespace App\Filament\Resources\OrderNumberResource\Pages;

use App\Filament\Resources\OrderNumberResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOrderNumbers extends ListRecords
{
    protected static string $resource = OrderNumberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
