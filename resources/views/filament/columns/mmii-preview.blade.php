@php
    $mmii = $getRecord()->mmii ?? ($getRecord() instanceof \App\Models\Mmii ? $getRecord() : null);
    if ($mmii && is_string($mmii->shape)) {
        $shape = json_decode($mmii->shape, true);
    } else {
        $shape = $mmii?->shape;
    }
    $background = $mmii?->background;
    $baseUrl = \Illuminate\Support\Facades\Storage::disk('public')->url('mmii');
    $bgUrl = $background ? \Illuminate\Support\Facades\Storage::disk('public')->url('background/' . $background) : null;

    $renderOrder = ['tete', 'maquillage', 'nez', 'yeux', 'sourcils', 'pilosite', 'cheveux', 'bouche', 'particularites', 'pull'];
@endphp

@if($mmii && $shape)
    <div class="relative rounded-full overflow-hidden"
         style="width: 2.5rem; height: 2.5rem; {{ $bgUrl ? "background-image: url('{$bgUrl}'); background-size: cover;" : 'background-color: #e5e7eb;' }}">
        @foreach($renderOrder as $part)
            @if(isset($shape[$part]) && isset($shape[$part]['img']) && $shape[$part]['img'])
                @php
                    $partEnum = \App\Enums\MMIIBodyPart::from($part);
                    $imgUrl = $baseUrl . '/' . $part . '/' . $shape[$part]['img'];
                    $hasColor = $partEnum->requiresColor() && isset($shape[$part]['color']);
                    $blendMode = $partEnum->mixBlenMode();
                @endphp

                @if($hasColor)
                    <div style="position: absolute; inset: 0; width: 100%; height: 100%;
                                background-color: {{ $shape[$part]['color'] }};
                                -webkit-mask-image: url('{{ $imgUrl }}');
                                mask-image: url('{{ $imgUrl }}');
                                -webkit-mask-size: 100% 100%;
                                mask-size: 100% 100%;">
                        <img src="{{ $imgUrl }}"
                             style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; mix-blend-mode: {{ $blendMode }};">
                    </div>
                @else
                    <img src="{{ $imgUrl }}"
                         style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;">
                @endif
            @endif
        @endforeach
    </div>
@else
    <div class="rounded-full" style="width: 2.5rem; height: 2.5rem; background-color: #e5e7eb;"></div>
@endif
