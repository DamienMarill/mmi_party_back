<?php

namespace App\Filament\Resources;

use App\Enums\CardRarity;
use App\Filament\Resources\CardVersionResource\Pages;
use App\Models\CardVersion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class CardVersionResource extends Resource
{
    protected static ?string $model = CardVersion::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationLabel = 'Versions de cartes';

    protected static ?string $modelLabel = 'Version de carte';

    protected static ?string $pluralModelLabel = 'Versions de cartes';

    protected static ?string $navigationGroup = 'Cartes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Select::make('card_template_id')
                            ->label('Template de carte')
                            ->relationship('cardTemplate', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('rarity')
                            ->label('Rarete')
                            ->options(
                                collect(CardRarity::cases())
                                    ->mapWithKeys(fn (CardRarity $rarity) => [$rarity->value => $rarity->label()])
                                    ->toArray()
                            )
                            ->required(),

                        Forms\Components\Select::make('image')
                            ->label('Image (fullart)')
                            ->options(function () {
                                $files = Storage::disk('public')->files('fullart');
                                return collect($files)
                                    ->mapWithKeys(fn (string $file) => [
                                        $file => basename($file),
                                    ])
                                    ->toArray();
                            })
                            ->searchable()
                            ->nullable(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('cardTemplate.mmii'))
            ->columns([
                Tables\Columns\ViewColumn::make('mmii_avatar')
                    ->label('MMII')
                    ->view('filament.columns.card-mmii-preview'),

                Tables\Columns\ImageColumn::make('image')
                    ->label('Fullart')
                    ->disk('public')
                    ->height(40)
                    ->defaultImageUrl(fn () => null)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('cardTemplate.name')
                    ->label('Template')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('rarity')
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

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Cree le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('rarity')
                    ->label('Rarete')
                    ->options(
                        collect(CardRarity::cases())
                            ->mapWithKeys(fn (CardRarity $rarity) => [$rarity->value => $rarity->label()])
                            ->toArray()
                    ),

                Tables\Filters\SelectFilter::make('card_template_id')
                    ->label('Template')
                    ->relationship('cardTemplate', 'name')
                    ->searchable()
                    ->preload(),
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
            'index' => Pages\ListCardVersions::route('/'),
            'create' => Pages\CreateCardVersion::route('/create'),
            'edit' => Pages\EditCardVersion::route('/{record}/edit'),
        ];
    }
}
