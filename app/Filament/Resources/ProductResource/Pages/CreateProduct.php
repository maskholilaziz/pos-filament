<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected array $recipeItems = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['recipeItems'])) {
            $this->recipeItems = $data['recipeItems'];
            unset($data['recipeItems']);
        }
        return $data;
    }

    protected function afterCreate(): void
    {
        if (!empty($this->recipeItems)) {
            $dataToSync = [];
            foreach ($this->recipeItems as $item) {
                if (!empty($item['ingredient_id']) && !empty($item['quantity_used'])) {
                    $dataToSync[$item['ingredient_id']] = ['quantity_used' => $item['quantity_used']];
                }
            }
            $this->getRecord()->ingredients()->sync($dataToSync);
        }
    }
}
