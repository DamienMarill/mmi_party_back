<?php

namespace App\Filament\Resources;

use App\Enums\HubType;
use App\Enums\RoomStatus;
use App\Filament\Resources\HubRoomResource\Pages;
use App\Models\HubRoom;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HubRoomResource extends Resource
{
    protected static ?string $model = HubRoom::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationLabel = 'Salons';

    protected static ?string $modelLabel = 'Salon';

    protected static ?string $pluralModelLabel = 'Salons';

    protected static ?string $navigationGroup = 'Logs';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options(
                                collect(HubType::cases())
                                    ->mapWithKeys(fn (HubType $type) => [$type->value => $type->value])
                                    ->toArray()
                            )
                            ->disabled(),

                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options(
                                collect(RoomStatus::cases())
                                    ->mapWithKeys(fn (RoomStatus $s) => [$s->value => $s->value])
                                    ->toArray()
                            )
                            ->required(),

                        Forms\Components\Select::make('player_one_id')
                            ->label('Joueur 1')
                            ->relationship('playerOne', 'name')
                            ->disabled(),

                        Forms\Components\Select::make('player_two_id')
                            ->label('Joueur 2')
                            ->relationship('playerTwo', 'name')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (HubType $state): string => match ($state) {
                        HubType::TRADE => 'info',
                        HubType::FIGHT => 'danger',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (RoomStatus $state): string => match ($state) {
                        RoomStatus::ACTIVE => 'success',
                        RoomStatus::COMPLETED => 'gray',
                        RoomStatus::ABANDONED => 'danger',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('playerOne.name')
                    ->label('Joueur 1')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('playerTwo.name')
                    ->label('Joueur 2')
                    ->searchable()
                    ->sortable()
                    ->placeholder('En attente'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options(
                        collect(HubType::cases())
                            ->mapWithKeys(fn (HubType $t) => [$t->value => $t->value])
                            ->toArray()
                    ),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(
                        collect(RoomStatus::cases())
                            ->mapWithKeys(fn (RoomStatus $s) => [$s->value => $s->value])
                            ->toArray()
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHubRooms::route('/'),
            'edit' => Pages\EditHubRoom::route('/{record}/edit'),
        ];
    }
}
