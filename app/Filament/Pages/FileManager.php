<?php

namespace App\Filament\Pages;

use BostjanOb\FilamentFileManager\Pages\FileManager as BaseFileManager;

class FileManager extends BaseFileManager
{
    protected static ?string $navigationIcon = 'heroicon-o-folder-open';

    protected static ?string $navigationLabel = 'Fichiers';

    protected static ?string $title = 'Gestionnaire de fichiers';

    protected static ?int $navigationSort = 99;

    protected string $disk = 'public';
}
