<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderNumberResource\Pages;
use App\Models\OrderNumber;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrderNumberResource extends Resource
{
    protected static ?string $model = OrderNumber::class;
    protected static ?string $navigationIcon = 'heroicon-o-identification';
    protected static ?string $navigationGroup = 'Manajemen Operasional';
    protected static ?string $navigationLabel = 'Nomor Pesanan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('number_label')->label('Label Nomor')->required()->unique(ignoreRecord: true),
                Forms\Components\Select::make('status')
                    ->options(['available' => 'Tersedia', 'in_use' => 'Dipakai'])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number_label')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'available' => 'success',
                        'in_use' => 'danger',
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListOrderNumbers::route('/')]; // Read-only di sini
    }
}
