<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Ingredient;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\TrashedFilter;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'Manajemen Produk';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('category_id')->relationship('category', 'name')->required(),
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\Textarea::make('description')->columnSpanFull(),
                Forms\Components\TextInput::make('purchase_price')->label('Harga Beli')->numeric()->prefix('Rp')->default(0),
                Forms\Components\TextInput::make('selling_price')->label('Harga Jual')->numeric()->prefix('Rp')->required(),
                Forms\Components\Select::make('station_id')
                    ->relationship('station', 'name')
                    ->required()
                    ->label('Stasiun Dapur'),
                Forms\Components\Toggle::make('is_active')->label('Aktifkan Produk')->default(true),

                Forms\Components\Section::make('Resep / Bahan Baku')
                    ->schema([
                        // Repeater diperbarui tanpa ->relationship()
                        Forms\Components\Repeater::make('recipeItems') // Nama diubah
                            ->schema([
                                Forms\Components\Select::make('ingredient_id')
                                    ->label('Bahan Baku')
                                    ->options(Ingredient::query()->pluck('name', 'id'))
                                    ->searchable()
                                    ->required(),
                                Forms\Components\TextInput::make('quantity_used')
                                    ->label('Jumlah Dibutuhkan')
                                    ->numeric()
                                    ->required(),
                            ])
                            ->columns(2),
                    ])->collapsible(),

                Forms\Components\CheckboxList::make('optionGroups')
                    ->relationship('optionGroups', 'name')
                    ->label('Grup Opsi yang Tersedia')
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('category.name')->sortable(),
                Tables\Columns\TextColumn::make('selling_price')->money('IDR')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->filters([TrashedFilter::make()])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
