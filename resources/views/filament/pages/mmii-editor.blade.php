<x-filament-panels::page>
    <div class="flex flex-col lg:flex-row gap-6">

        {{-- Preview --}}
        <div class="flex flex-col items-center gap-4 lg:sticky lg:top-4 lg:self-start">
            <div class="relative rounded-2xl overflow-hidden shadow-lg border-2 border-gray-200 dark:border-gray-700"
                 @if($background)
                 style="width: 18rem; height: 18rem; background-image: url('{{ $this->getBackgroundUrl($background) }}'); background-size: cover;"
                 @else
                 style="width: 18rem; height: 18rem; background-color: #e5e7eb;"
                 @endif
            >
                @foreach($this->getRenderOrder() as $part)
                    @if(isset($shape[$part]) && isset($shape[$part]['img']) && $shape[$part]['img'])
                        @php
                            $partEnum = \App\Enums\MMIIBodyPart::from($part);
                            $imgUrl = $this->getAssetUrl($part . '/' . $shape[$part]['img']);
                            $hasColor = $partEnum->requiresColor() && isset($shape[$part]['color']);
                            $blendMode = $partEnum->mixBlenMode();
                        @endphp

                        @if($hasColor)
                            <div class="absolute inset-0 w-full h-full"
                                 style="background-color: {{ $shape[$part]['color'] }};
                                        -webkit-mask-image: url('{{ $imgUrl }}');
                                        mask-image: url('{{ $imgUrl }}');
                                        -webkit-mask-size: 100% 100%;
                                        mask-size: 100% 100%;">
                                <img src="{{ $imgUrl }}"
                                     class="absolute top-0 left-0 w-full h-full"
                                     style="mix-blend-mode: {{ $blendMode }};">
                            </div>
                        @else
                            <img src="{{ $imgUrl }}"
                                 class="absolute top-0 left-0 w-full h-full">
                        @endif
                    @endif
                @endforeach
            </div>

            <p class="text-sm text-gray-500 dark:text-gray-400">
                @if($record->baseUser)
                    {{ $record->baseUser->name }}
                @else
                    MMII #{{ $record->id }}
                @endif
            </p>

            <x-filament::button wire:click="save" icon="heroicon-o-check" color="success" size="lg" class="w-full">
                Sauvegarder
            </x-filament::button>
        </div>

        {{-- Editor --}}
        <div class="flex-1 flex flex-col gap-4">

            {{-- Part tabs --}}
            <div class="flex flex-wrap gap-1">
                @foreach($this->getPartLabels() as $partKey => $partLabel)
                    <x-filament::button
                        wire:click="setActivePart('{{ $partKey }}')"
                        :color="$activePart === $partKey ? 'primary' : 'gray'"
                        size="sm"
                    >
                        {{ $partLabel }}
                    </x-filament::button>
                @endforeach
                <x-filament::button
                    wire:click="$set('activePart', 'background')"
                    :color="$activePart === 'background' ? 'primary' : 'gray'"
                    size="sm"
                >
                    Fond
                </x-filament::button>
            </div>

            @if($activePart === 'background')
                {{-- Background selector --}}
                <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4">
                    <h3 class="text-base font-semibold mb-3">Fond</h3>
                    <div class="grid grid-cols-6 sm:grid-cols-8 gap-2">
                        @foreach($backgrounds as $bg)
                            <button
                                wire:click="selectBackground('{{ $bg }}')"
                                class="aspect-square rounded-lg overflow-hidden border-2 transition-all hover:scale-105 {{ $background === $bg ? 'border-primary-500 ring-2 ring-primary-500/50' : 'border-gray-200 dark:border-gray-700' }}"
                            >
                                <img src="{{ $this->getBackgroundUrl($bg) }}" class="w-full h-full object-cover" alt="{{ $bg }}">
                            </button>
                        @endforeach
                    </div>
                </div>
            @else
                {{-- Color picker --}}
                @if(isset($partsData[$activePart]) && $partsData[$activePart]['requiresColor'] && $partsData[$activePart]['availableColors'])
                    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4">
                        <h3 class="text-base font-semibold mb-3">Couleur — {{ $this->getPartLabels()[$activePart] }}</h3>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($partsData[$activePart]['availableColors'] as $color)
                                <button
                                    wire:click="selectColor('{{ $activePart }}', '{{ $color }}')"
                                    class="rounded-full border-2 transition-all hover:scale-110 {{ (isset($shape[$activePart]['color']) && $shape[$activePart]['color'] === $color) ? 'border-primary-500 ring-2 ring-primary-500/50 scale-110' : 'border-gray-300 dark:border-gray-600' }}"
                                    style="width: 1.75rem; height: 1.75rem; background-color: {{ $color }};"
                                    title="{{ $color }}"
                                ></button>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Image selector --}}
                @if(isset($partsData[$activePart]))
                    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4">
                        <h3 class="text-base font-semibold mb-3">Forme — {{ $this->getPartLabels()[$activePart] }}</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach($partsData[$activePart]['files'] as $file)
                                @php
                                    $isSelected = isset($shape[$activePart]['img']) && $shape[$activePart]['img'] === $file;
                                    $previewColor = ($partsData[$activePart]['requiresColor'] && isset($shape[$activePart]['color']))
                                        ? $shape[$activePart]['color']
                                        : '#666';
                                @endphp
                                <button
                                    wire:click="selectImage('{{ $activePart }}', '{{ $file }}')"
                                    class="rounded-lg border-2 transition-all hover:scale-105 overflow-hidden {{ $isSelected ? 'border-primary-500 ring-2 ring-primary-500/50' : 'border-gray-200 dark:border-gray-700' }}"
                                    style="width: 5rem; height: 5rem; background-color: {{ $partsData[$activePart]['requiresColor'] ? $previewColor : 'transparent' }};"
                                >
                                    <img src="{{ $this->getAssetUrl($activePart . '/' . $file) }}"
                                         class="w-full h-full object-contain"
                                         style="{{ $partsData[$activePart]['requiresColor'] ? 'mix-blend-mode: multiply;' : '' }}"
                                         alt="{{ $file }}">
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-filament-panels::page>
