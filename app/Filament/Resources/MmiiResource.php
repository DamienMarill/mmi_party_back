<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MmiiResource\Pages;
use App\Models\Mmii;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MmiiResource extends Resource
{
    protected static ?string $model = Mmii::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'MMIIs';

    protected static ?string $modelLabel = 'MMII';

    protected static ?string $pluralModelLabel = 'MMIIs';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ViewColumn::make('avatar')
                    ->label('Aperçu')
                    ->view('filament.columns.mmii-preview'),

                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->limit(8),

                Tables\Columns\TextColumn::make('baseUser.name')
                    ->label('Utilisateur')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Aucun'),

                Tables\Columns\TextColumn::make('background')
                    ->label('Fond')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMmiis::route('/'),
            'edit' => Pages\EditMmii::route('/{record}/edit'),
        ];
    }
}
