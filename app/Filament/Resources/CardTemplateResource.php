<?php

namespace App\Filament\Resources;

use App\Enums\CardTypes;
use App\Filament\Resources\CardTemplateResource\Pages;
use App\Models\CardTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CardTemplateResource extends Resource
{
    protected static ?string $model = CardTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Templates de cartes';

    protected static ?string $modelLabel = 'Template de carte';

    protected static ?string $pluralModelLabel = 'Templates de cartes';

    protected static ?string $navigationGroup = 'Cartes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations generales')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options(
                                collect(CardTypes::cases())
                                    ->mapWithKeys(fn (CardTypes $type) => [$type->value => $type->label()])
                                    ->toArray()
                            )
                            ->required()
                            ->reactive(),

                        Forms\Components\TextInput::make('level')
                            ->label('Niveau')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(3)
                            ->visible(fn (Forms\Get $get): bool => $get('type') === CardTypes::STUDENT->value),
                    ])->columns(3),

                Forms\Components\Section::make('Relations')
                    ->schema([
                        Forms\Components\Select::make('mmii_id')
                            ->label('MMII')
                            ->relationship('mmii', 'id')
                            ->searchable()
                            ->nullable(),

                        Forms\Components\Select::make('base_user')
                            ->label('Utilisateur de base')
                            ->relationship('baseUser', 'name')
                            ->searchable()
                            ->nullable(),
                    ])->columns(2),

                Forms\Components\Section::make('Donnees techniques')
                    ->schema([
                        Forms\Components\KeyValue::make('stats')
                            ->label('Statistiques')
                            ->nullable(),

                        Forms\Components\KeyValue::make('shape')
                            ->label('Forme')
                            ->nullable(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('mmii'))
            ->columns([
                Tables\Columns\ViewColumn::make('mmii_avatar')
                    ->label('MMII')
                    ->view('filament.columns.card-mmii-preview'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn (CardTypes $state): string => $state->label())
                    ->sortable(),

                Tables\Columns\TextColumn::make('level')
                    ->label('Niveau')
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('baseUser.name')
                    ->label('Utilisateur de base')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('card_versions_count')
                    ->label('Versions')
                    ->counts('cardVersions')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Cree le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options(
                        collect(CardTypes::cases())
                            ->mapWithKeys(fn (CardTypes $type) => [$type->value => $type->label()])
                            ->toArray()
                    ),

                Tables\Filters\SelectFilter::make('level')
                    ->label('Niveau')
                    ->options([
                        1 => 'Niveau 1',
                        2 => 'Niveau 2',
                        3 => 'Niveau 3',
                    ]),
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
            'index' => Pages\ListCardTemplates::route('/'),
            'create' => Pages\CreateCardTemplate::route('/create'),
            'edit' => Pages\EditCardTemplate::route('/{record}/edit'),
        ];
    }
}
