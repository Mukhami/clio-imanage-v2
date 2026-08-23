<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-neutral-100 antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-md flex-col gap-6">
                <a href="{{ route('home') }}" class="flex justify-center" wire:navigate>
                    <img src="/logo/png/matterlynk-horizontal-light-1200.png" alt="{{ config('app.name', 'MatterLynk') }}" class="h-10 w-auto" />
                </a>

                <div class="flex flex-col gap-6">
                    <div class="rounded-xl border border-neutral-200 bg-white dark:border-depth-400/20 dark:bg-depth-600 shadow-sm">
                        <div class="px-10 py-8">{{ $slot }}</div>
                    </div>
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
