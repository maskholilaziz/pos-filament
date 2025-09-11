<?php

namespace App\Filament\Resources\BundleResource\Pages;

use App\Filament\Resources\BundleResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateBundle extends CreateRecord
{
    protected static string $resource = BundleResource::class;

    protected array $bundleItems = [];

    // Pisahkan data bundle dan data item-itemnya sebelum data utama dibuat
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->bundleItems = $data['bundleItems'];
        unset($data['bundleItems']);
        return $data;
    }

    // Setelah bundle utama dibuat, lampirkan (sync) produk-produknya
    protected function afterCreate(): void
    {
        $dataToSync = [];
        foreach ($this->bundleItems as $item) {
            $dataToSync[$item['product_id']] = ['quantity' => $item['quantity']];
        }

        $this->getRecord()->products()->sync($dataToSync);
    }
}
