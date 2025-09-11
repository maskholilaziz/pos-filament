<?php

namespace App\Filament\Resources\BundleResource\Pages;

use App\Filament\Resources\BundleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditBundle extends EditRecord
{
    protected static string $resource = BundleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Ini adalah method yang akan dieksekusi saat halaman pertama kali dimuat.
     * Kita akan secara manual mengisi data form di sini.
     */
    public function mount(int | string $record): void
    {
        parent::mount($record);

        // Ambil data dari relasi 'products'
        $bundleItems = $this->getRecord()->products->map(function ($product) {
            return [
                'product_id' => $product->id,
                'quantity' => $product->pivot->quantity,
            ];
        })->toArray();

        // Isi form secara eksplisit dengan data yang sudah diformat
        $this->form->fill(
            // Gabungkan data utama record dengan data repeater
            array_merge($this->getRecord()->toArray(), ['bundleItems' => $bundleItems])
        );
    }

    /**
     * Method ini akan menangani proses penyimpanan data.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Pisahkan data item dari data utama bundle
        $bundleItems = $data['bundleItems'];
        unset($data['bundleItems']);

        // Update data utama bundle (nama, harga, dll.)
        $record->update($data);

        // Siapkan data untuk disinkronkan ke tabel pivot
        $dataToSync = [];
        foreach ($bundleItems as $item) {
            $dataToSync[$item['product_id']] = ['quantity' => $item['quantity']];
        }

        // Sinkronkan relasi produk
        $record->products()->sync($dataToSync);

        return $record;
    }
}
