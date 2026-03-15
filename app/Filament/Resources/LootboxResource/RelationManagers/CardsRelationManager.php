<?php

namespace App\Filament\Resources\LootboxResource\RelationManagers;

use App\Enums\CardRarity;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CardsRelationManager extends RelationManager
{
    protected static string $relationship = 'cards';

    protected static ?string $title = 'Cartes obtenues';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['cardVersion.cardTemplate.mmii']))
            ->columns([
                Tables\Columns\ViewColumn::make('mmii_avatar')
                    ->label('MMII')
                    ->view('filament.columns.card-mmii-preview'),

                Tables\Columns\TextColumn::make('cardVersion.cardTemplate.name')
                    ->label('Carte')
                    ->sortable(),

                Tables\Columns\TextColumn::make('cardVersion.rarity')
                    ->label('Rarete')
                    ->formatStateUsing(fn (CardRarity $state): string => $state->label())
                    ->badge()
                    ->color(fn (CardRarity $state): string => match ($state) {
                        CardRarity::COMMON => 'gray',
                        CardRarity::UNCOMMON => 'success',
                        CardRarity::RARE => 'info',
                        CardRarity::EPIC => 'warning',
                    })
                    ->sortable(),

                Tables\Columns\ImageColumn::make('cardVersion.image')
                    ->label('Fullart')
                    ->disk('public')
                    ->height(40)
                    ->state(fn ($record) => $record->cardVersion?->image ? 'fullart/' . $record->cardVersion->image : null)
                    ->defaultImageUrl(fn () => null),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Obtenue le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
