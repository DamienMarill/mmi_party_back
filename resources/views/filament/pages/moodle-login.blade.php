<x-filament-panels::page.simple>
    <div class="flex flex-col items-center gap-6">
        <p class="text-sm text-center text-gray-500 dark:text-gray-400">
            Connectez-vous avec votre compte Moodle pour accéder au panel d'administration.
        </p>

        <a href="{{ url('/api/auth/moodle/redirect?from=admin') }}"
           class="w-full fi-btn fi-btn-size-lg relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-btn-color-primary bg-primary-600 text-white hover:bg-primary-500 focus-visible:ring-primary-500/50 dark:bg-primary-500 dark:hover:bg-primary-400 dark:focus-visible:ring-primary-400/50 px-6 py-3 text-center block">
            Se connecter via Moodle
        </a>

        @if (request()->query('error'))
            <div class="w-full rounded-lg bg-danger-50 p-4 text-sm text-danger-600 dark:bg-danger-400/10 dark:text-danger-400">
                {{ request()->query('error') }}
            </div>
        @endif
    </div>
</x-filament-panels::page.simple>
