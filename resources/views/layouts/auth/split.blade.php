<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
            {{-- Left panel: dark brand panel --}}
            <div class="relative hidden h-full flex-col p-10 text-white lg:flex" style="background: linear-gradient(160deg, #0c0636 0%, #095169 60%, #059b9a 100%);">
                <a href="{{ route('home') }}" class="relative z-20 flex items-center" wire:navigate>
                    <img src="/logo/matterlynk-horizontal-dark.svg" alt="{{ config('app.name', 'MatterLynk') }}" class="h-8 w-auto" />
                </a>

                <div class="relative z-20 mt-auto">
                    <blockquote class="space-y-2">
                        <p class="text-lg font-medium text-white/90">&ldquo;Connect Clio and iManage. Automatically.&rdquo;</p>
                        <footer class="text-sm font-semibold text-core-400">MatterLynk</footer>
                    </blockquote>
                </div>
            </div>

            {{-- Right panel: form --}}
            <div class="w-full lg:p-8">
                <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
                    {{-- Mobile logo --}}
                    <a href="{{ route('home') }}" class="flex justify-center lg:hidden" wire:navigate>
                        <img src="/logo/png/matterlynk-horizontal-light-1200.png" alt="{{ config('app.name', 'MatterLynk') }}" class="h-10 w-auto" />
                    </a>
                    {{ $slot }}
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
