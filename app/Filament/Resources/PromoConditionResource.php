<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromoConditionResource\Pages;
use App\Models\PromoCondition;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PromoConditionResource extends Resource
{
    protected static ?string $model = PromoCondition::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift-top';

    protected static ?string $navigationLabel = 'Promos';

    protected static ?string $modelLabel = 'Promo';

    protected static ?string $pluralModelLabel = 'Promos';

    protected static ?string $navigationGroup = 'Cartes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Carte')
                    ->schema([
                        Forms\Components\Select::make('card_version_id')
                            ->label('Version de carte')
                            ->relationship('cardVersion', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->cardTemplate?->name . ' (' . $record->rarity->label() . ')')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),

                Forms\Components\Section::make('Condition')
                    ->schema([
                        Forms\Components\Select::make('condition_type')
                            ->label('Type de condition')
                            ->options([
                                'date_range' => 'Plage de dates',
                                'App\\PromoCheckers\\CollectionCompleteChecker' => 'Collection complete (nb cartes)',
                                'App\\PromoCheckers\\LootboxCountChecker' => 'Nombre de lootboxes ouvertes',
                            ])
                            ->required()
                            ->live(),

                        Forms\Components\KeyValue::make('condition_data')
                            ->label('Parametres')
                            ->keyLabel('Cle')
                            ->valueLabel('Valeur')
                            ->nullable()
                            ->visible(fn (Forms\Get $get) => $get('condition_type') && $get('condition_type') !== 'date_range'),
                    ]),

                Forms\Components\Section::make('Disponibilite')
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Debut')
                            ->nullable(),

                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('Fin')
                            ->nullable(),

                        Forms\Components\Toggle::make('active')
                            ->label('Active')
                            ->default(true),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['cardVersion.cardTemplate', 'unlocks']))
            ->columns([
                Tables\Columns\TextColumn::make('cardVersion.cardTemplate.name')
                    ->label('Carte')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('condition_type')
                    ->label('Condition')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'date_range' => 'Plage de dates',
                        default => class_basename($state),
                    })
                    ->badge(),

                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Debut')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Fin')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\IconColumn::make('active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('unlocks_count')
                    ->label('Debloques')
                    ->counts('unlocks')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('active')
                    ->label('Actives seulement')
                    ->query(fn ($query) => $query->where('active', true))
                    ->default(true)
                    ->toggle(),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPromoConditions::route('/'),
            'create' => Pages\CreatePromoCondition::route('/create'),
            'edit' => Pages\EditPromoCondition::route('/{record}/edit'),
        ];
    }
}
