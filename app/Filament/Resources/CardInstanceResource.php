<?php

namespace App\Filament\Resources;

use App\Enums\CardRarity;
use App\Filament\Resources\CardInstanceResource\Pages;
use App\Models\CardInstance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CardInstanceResource extends Resource
{
    protected static ?string $model = CardInstance::class;

    protected static ?string $navigationIcon = 'heroicon-o-square-3-stack-3d';

    protected static ?string $navigationLabel = 'Cartes obtenues';

    protected static ?string $modelLabel = 'Carte obtenue';

    protected static ?string $pluralModelLabel = 'Cartes obtenues';

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

                        Forms\Components\Select::make('card_version_id')
                            ->label('Version de carte')
                            ->relationship('cardVersion', 'id')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('lootbox_id')
                            ->label('Lootbox')
                            ->relationship('lootbox', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['cardVersion.cardTemplate.mmii', 'user', 'lootbox']))
            ->columns([
                Tables\Columns\ViewColumn::make('mmii_avatar')
                    ->label('MMII')
                    ->view('filament.columns.card-mmii-preview'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('cardVersion.cardTemplate.name')
                    ->label('Carte')
                    ->searchable()
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
                    ->defaultImageUrl(fn () => null)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('lootbox.name')
                    ->label('Lootbox')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Obtenue le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Utilisateur')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('rarity')
                    ->label('Rarete')
                    ->options(
                        collect(CardRarity::cases())
                            ->mapWithKeys(fn (CardRarity $rarity) => [$rarity->value => $rarity->label()])
                            ->toArray()
                    )
                    ->query(fn ($query, array $data) => $data['value']
                        ? $query->whereHas('cardVersion', fn ($q) => $q->where('rarity', $data['value']))
                        : $query
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListCardInstances::route('/'),
        ];
    }
}
