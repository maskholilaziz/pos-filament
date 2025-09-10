<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'Manajemen Penjualan';
    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')->searchable()->label('No. Invoice'),
                Tables\Columns\TextColumn::make('customer_name')->searchable()->label('Pelanggan'),
                Tables\Columns\TextColumn::make('cashier.name')->label('Kasir'),
                Tables\Columns\TextColumn::make('total_price')->money('IDR')->sortable()->label('Total'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->label('Tanggal Transaksi'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Pesanan')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('invoice_number')->label('No. Invoice'),
                        Infolists\Components\TextEntry::make('customer_name')->label('Pelanggan'),
                        Infolists\Components\TextEntry::make('cashier.name')->label('Kasir'),
                        Infolists\Components\TextEntry::make('created_at')->dateTime()->label('Waktu Transaksi'),
                        Infolists\Components\TextEntry::make('total_price')->money('IDR'),
                        Infolists\Components\TextEntry::make('amount_paid')->money('IDR')->label('Uang Bayar'),
                        Infolists\Components\TextEntry::make('change')->money('IDR')->label('Kembalian'),
                    ]),
                Infolists\Components\Section::make('Detail Item')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->columns(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('product_name')->label('Produk'),
                                Infolists\Components\TextEntry::make('quantity')->label('Jumlah'),
                                Infolists\Components\TextEntry::make('total_price')->money('IDR')->label('Subtotal'),
                            ]),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
