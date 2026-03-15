<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TradeLogResource\Pages;
use App\Models\TradeLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TradeLogResource extends Resource
{
    protected static ?string $model = TradeLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationLabel = 'Échanges';

    protected static ?string $modelLabel = 'Échange';

    protected static ?string $pluralModelLabel = 'Échanges';

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
                        Forms\Components\Select::make('user_1_id')
                            ->label('Joueur 1')
                            ->relationship('user1', 'name')
                            ->disabled(),

                        Forms\Components\Select::make('user_2_id')
                            ->label('Joueur 2')
                            ->relationship('user2', 'name')
                            ->disabled(),

                        Forms\Components\Select::make('card_instance_1_id')
                            ->label('Carte joueur 1')
                            ->relationship('cardInstance1', 'id')
                            ->disabled(),

                        Forms\Components\Select::make('card_instance_2_id')
                            ->label('Carte joueur 2')
                            ->relationship('cardInstance2', 'id')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user1.name')
                    ->label('Joueur 1')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('cardInstance1.cardVersion.cardTemplate.name')
                    ->label('Carte J1')
                    ->limit(20),

                Tables\Columns\TextColumn::make('user2.name')
                    ->label('Joueur 2')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('cardInstance2.cardVersion.cardTemplate.name')
                    ->label('Carte J2')
                    ->limit(20),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('user_1_id')
                    ->label('Joueur 1')
                    ->relationship('user1', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('user_2_id')
                    ->label('Joueur 2')
                    ->relationship('user2', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTradeLogs::route('/'),
        ];
    }
}
