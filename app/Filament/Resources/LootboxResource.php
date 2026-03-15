<?php

namespace App\Filament\Resources;

use App\Enums\LootboxTypes;
use App\Filament\Resources\LootboxResource\Pages;
use App\Filament\Resources\LootboxResource\RelationManagers;
use App\Models\Lootbox;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LootboxResource extends Resource
{
    protected static ?string $model = Lootbox::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationLabel = 'Lootboxes';

    protected static ?string $modelLabel = 'Lootbox';

    protected static ?string $pluralModelLabel = 'Lootboxes';

    protected static ?string $navigationGroup = 'Logs';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Utilisateur')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options(
                                collect(LootboxTypes::cases())
                                    ->mapWithKeys(fn (LootboxTypes $type) => [$type->value => $type->label()])
                                    ->toArray()
                            )
                            ->required(),

                        Forms\Components\DateTimePicker::make('slot_used_at')
                            ->label('Slot utilise le')
                            ->nullable(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn (LootboxTypes $state): string => $state->label())
                    ->badge()
                    ->color(fn (LootboxTypes $state): string => match ($state) {
                        LootboxTypes::QUOTIDIAN => 'primary',
                        LootboxTypes::STARTER => 'success',
                        LootboxTypes::PURCHASED => 'warning',
                        LootboxTypes::GIFTED => 'info',
                        LootboxTypes::MISC => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('slot_used_at')
                    ->label('Slot utilise le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('Non utilise'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Cree le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options(
                        collect(LootboxTypes::cases())
                            ->mapWithKeys(fn (LootboxTypes $type) => [$type->value => $type->label()])
                            ->toArray()
                    ),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Utilisateur')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('slot_used_at')
                    ->label('Utilise')
                    ->query(fn ($query) => $query->whereNotNull('slot_used_at'))
                    ->toggle(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Cree depuis'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Cree jusqu\'a'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['created_from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CardsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLootboxes::route('/'),
            'create' => Pages\CreateLootbox::route('/create'),
            'edit' => Pages\EditLootbox::route('/{record}/edit'),
        ];
    }
}
