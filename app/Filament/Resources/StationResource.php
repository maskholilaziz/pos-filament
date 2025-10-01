<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StationResource\Pages;
use App\Models\Station;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StationResource extends Resource
{
    protected static ?string $model = Station::class;
    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';
    protected static ?string $navigationGroup = 'Manajemen Operasional';
    protected static ?string $navigationLabel = 'Stasiun Dapur';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->unique(ignoreRecord: true),
            Forms\Components\Select::make('output_type')
                ->options(['kds' => 'Layar (KDS)', 'printer' => 'Printer'])
                ->required()->label('Metode Output'),
        ]);
    }
    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name'),
            Tables\Columns\TextColumn::make('output_type')->badge(),
        ])->actions([Tables\Actions\EditAction::make()]);
    }
    public static function getPages(): array
    {
        return ['index' => Pages\ListStations::route('/'), 'create' => Pages\CreateStation::route('/create'), 'edit' => Pages\EditStation::route('/{record}/edit')];
    }
}
