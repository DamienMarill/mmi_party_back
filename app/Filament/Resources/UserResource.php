<?php

namespace App\Filament\Resources;

use App\Enums\UserGroups;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Utilisateurs';

    protected static ?string $modelLabel = 'Utilisateur';

    protected static ?string $pluralModelLabel = 'Utilisateurs';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations personnelles')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('um_email')
                            ->label('Email universitaire')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\Select::make('groupe')
                            ->label('Groupe')
                            ->options(
                                collect(UserGroups::cases())
                                    ->mapWithKeys(fn (UserGroups $group) => [$group->value => $group->label()])
                                    ->toArray()
                            )
                            ->required(),

                        Forms\Components\Toggle::make('is_admin')
                            ->label('Administrateur')
                            ->default(false),
                    ])->columns(2),

                Forms\Components\Section::make('Moodle')
                    ->schema([
                        Forms\Components\TextInput::make('moodle_id')
                            ->label('Moodle ID')
                            ->numeric()
                            ->nullable(),

                        Forms\Components\TextInput::make('moodle_username')
                            ->label('Nom d\'utilisateur Moodle')
                            ->maxLength(255)
                            ->nullable(),
                    ])->columns(2),

                Forms\Components\Section::make('Securite')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->label('Mot de passe')
                            ->password()
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? bcrypt($state) : null)
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255),

                        Forms\Components\DateTimePicker::make('email_verified_at')
                            ->label('Email verifie le')
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
                    ->view('filament.columns.mmii-preview'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('um_email')
                    ->label('Email UM')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('groupe')
                    ->label('Groupe')
                    ->formatStateUsing(fn (UserGroups $state): string => $state->label())
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_admin')
                    ->label('Admin')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('moodle_username')
                    ->label('Moodle')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('email_verified_at')
                    ->label('Verifie le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Cree le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('groupe')
                    ->label('Groupe')
                    ->options(
                        collect(UserGroups::cases())
                            ->mapWithKeys(fn (UserGroups $group) => [$group->value => $group->label()])
                            ->toArray()
                    ),

                Tables\Filters\TernaryFilter::make('is_admin')
                    ->label('Administrateur'),

                Tables\Filters\TernaryFilter::make('email_verified_at')
                    ->label('Email verifie')
                    ->nullable(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
