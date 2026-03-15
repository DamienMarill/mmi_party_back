<?php

namespace App\Filament\Resources\MmiiResource\Pages;

use App\Enums\MMIIBodyPart;
use App\Filament\Resources\MmiiResource;
use App\Models\Mmii;
use App\Services\MMIIService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditMmii extends EditRecord
{
    protected static string $resource = MmiiResource::class;

    protected static string $view = 'filament.pages.mmii-editor';

    public array $shape = [];
    public string $background = '';
    public string $activePart = 'tete';
    public array $partsData = [];
    public array $backgrounds = [];

    private static array $partLabels = [
        'tete' => 'Tête',
        'yeux' => 'Yeux',
        'sourcils' => 'Sourcils',
        'nez' => 'Nez',
        'bouche' => 'Bouche',
        'cheveux' => 'Cheveux',
        'pilosite' => 'Pilosité',
        'maquillage' => 'Maquillage',
        'particularites' => 'Particularités',
        'pull' => 'Pull',
    ];

    private static array $renderOrder = [
        'tete', 'maquillage', 'nez', 'yeux', 'sourcils',
        'pilosite', 'cheveux', 'bouche', 'particularites', 'pull',
    ];

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $mmiiService = app(MMIIService::class);
        $this->partsData = $mmiiService->getAvailablePartsWithAssets();
        $this->backgrounds = $mmiiService->getBackgroundsFiles();

        $this->shape = $this->record->shape ?? [];
        $this->background = $this->record->background ?? '';
    }

    public function getPartLabels(): array
    {
        return self::$partLabels;
    }

    public function getRenderOrder(): array
    {
        return self::$renderOrder;
    }

    public function getAssetUrl(string $path): string
    {
        return Storage::disk('public')->url('mmii/' . $path);
    }

    public function getBackgroundUrl(string $bg): string
    {
        return Storage::disk('public')->url('background/' . $bg);
    }

    public function setActivePart(string $part): void
    {
        $this->activePart = $part;
    }

    public function selectImage(string $part, string $img): void
    {
        if (!isset($this->shape[$part])) {
            $this->shape[$part] = ['img' => $img];
        } else {
            $this->shape[$part]['img'] = $img;
        }
    }

    public function selectColor(string $part, string $color): void
    {
        if (!isset($this->shape[$part])) {
            $this->shape[$part] = ['img' => '', 'color' => $color];
        } else {
            $this->shape[$part]['color'] = $color;
        }
    }

    public function selectBackground(string $bg): void
    {
        $this->background = $bg;
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        $this->record->shape = $this->shape;
        $this->record->background = $this->background;
        $this->record->save();

        Notification::make()
            ->title('MMII sauvegardé')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
