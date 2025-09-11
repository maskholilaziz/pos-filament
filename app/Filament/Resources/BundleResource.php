<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BundleResource\Pages;
use App\Models\Bundle;
use App\Models\Product; // <-- Tambahkan ini
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get; // <-- Tambahkan ini
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BundleResource extends Resource
{
    protected static ?string $model = Bundle::class;
    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';
    protected static ?string $navigationGroup = 'Manajemen Produk';
    protected static ?string $navigationLabel = 'Paket Promo';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->required()->columnSpanFull(),
                Forms\Components\Textarea::make('description')->columnSpanFull(),
                Forms\Components\TextInput::make('price')->numeric()->prefix('Rp')->required(),
                Forms\Components\Toggle::make('is_active')->default(true),
                Forms\Components\Section::make('Jadwal Promo (Opsional)')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')->label('Tanggal Mulai'),
                        Forms\Components\DatePicker::make('end_date')->label('Tanggal Berakhir'),
                    ])->columns(2),

                // PASTIKAN NAMA REPEATER ADALAH 'bundleItems'
                Forms\Components\Repeater::make('bundleItems')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('Produk')
                            ->options(Product::query()->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->disableOptionWhen(function ($value, $state, Get $get) {
                                return collect($get('../../bundleItems'))
                                    ->reject(fn($item) => $item['product_id'] === $state)
                                    ->pluck('product_id')
                                    ->contains($value);
                            })
                            ->reactive(),
                        Forms\Components\TextInput::make('quantity')->numeric()->required()->default(1),
                    ])
                    ->label('Isi Paket')
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('price')->money('IDR'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('start_date')->date(),
                Tables\Columns\TextColumn::make('end_date')->date(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBundles::route('/'),
            'create' => Pages\CreateBundle::route('/create'),
            'edit' => Pages\EditBundle::route('/{record}/edit'),
        ];
    }
}
