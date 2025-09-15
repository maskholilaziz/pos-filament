<?php

namespace App\Filament\Resources\OrderNumberResource\Pages;

use App\Filament\Resources\OrderNumberResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrderNumber extends EditRecord
{
    protected static string $resource = OrderNumberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
