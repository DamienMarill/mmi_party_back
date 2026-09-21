<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Services\SchoolYearTransitionService;
use App\Filament\Resources\UserResource;
use App\Models\User;
use DomainException;
use Filament\Forms\Components\TextInput;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('yearTransition')
                ->label('Transition année scolaire')
                ->icon('heroicon-o-academic-cap')
                ->color('warning')
                ->form([
                    TextInput::make('school_year')
                        ->label('Année scolaire')
                        ->placeholder('2026-2027')
                        ->required()
                        ->regex('/^\d{4}-\d{4}$/')
                        ->helperText('Format attendu : YYYY-YYYY (ex: 2026-2027).'),
                    TextInput::make('mmi1_target')
                        ->label('Effectif visé MMI1')
                        ->numeric()
                        ->minValue(0)
                        ->required(),
                    TextInput::make('mmi2_target')
                        ->label('Effectif visé MMI2')
                        ->numeric()
                        ->minValue(0)
                        ->required(),
                    TextInput::make('mmi3_target')
                        ->label('Effectif visé MMI3')
                        ->numeric()
                        ->minValue(0)
                        ->required(),
                ])
                ->action(function (array $data, SchoolYearTransitionService $service): void {
                    try {
                        $executor = auth()->user();

                        $result = $service->execute(
                            $data['school_year'],
                            [
                                'mmi1' => (int) $data['mmi1_target'],
                                'mmi2' => (int) $data['mmi2_target'],
                                'mmi3' => (int) $data['mmi3_target'],
                            ],
                            $executor instanceof User ? $executor : null
                        );

                        Notification::make()
                            ->success()
                            ->title("Transition {$result['school_year']} exécutée")
                            ->body("Bots alumni supprimés : {$result['deleted_alumni_bots']}. Les bots MMI ont été recalculés selon les effectifs saisis.")
                            ->send();
                    } catch (DomainException $exception) {
                        Notification::make()
                            ->danger()
                            ->title('Transition refusée')
                            ->body($exception->getMessage())
                            ->send();
                    }
                })
                ->requiresConfirmation(),
        ];
    }
}
