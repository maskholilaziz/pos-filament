<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    public function mount(int | string $record): void
    {
        parent::mount($record);

        $recipeItems = $this->getRecord()->ingredients->map(function ($ingredient) {
            return [
                'ingredient_id' => $ingredient->id,
                'quantity_used' => $ingredient->pivot->quantity_used,
            ];
        })->toArray();

        $this->form->fill(
            array_merge($this->getRecord()->toArray(), ['recipeItems' => $recipeItems])
        );
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $recipeItems = $data['recipeItems'] ?? [];
        unset($data['recipeItems']);

        $record->update($data);

        $dataToSync = [];
        if (!empty($recipeItems)) {
            foreach ($recipeItems as $item) {
                if (!empty($item['ingredient_id']) && !empty($item['quantity_used'])) {
                    $dataToSync[$item['ingredient_id']] = ['quantity_used' => $item['quantity_used']];
                }
            }
        }
        $record->ingredients()->sync($dataToSync);

        return $record;
    }
}
