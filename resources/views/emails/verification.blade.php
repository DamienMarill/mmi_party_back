@component('mail::layout')
    @slot('header')
        @component('mail::header', ['url' => config('app.url')])
            {{ config('app.name') }}
        @endcomponent
    @endslot

    # Vérification de votre email universitaire

    Votre code de vérification est : {{ $verificationCode }}

    Ce code est valable pendant 1 heure.

    @component('mail::button', ['url' => config('app.url')])
        Retourner à l'application
    @endcomponent

    Merci,<br>
    {{ config('app.name') }}

    @slot('footer')
        @component('mail::footer')
            © {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.
        @endcomponent
    @endslot
@endcomponent
