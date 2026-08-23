@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand :name="config('app.name', 'MatterLynk')" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-core-600">
            <img src="/logo/matterlynk-icon-white.svg" alt="{{ config('app.name', 'MatterLynk') }}" class="size-5" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand :name="config('app.name', 'MatterLynk')" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-core-600">
            <img src="/logo/matterlynk-icon-white.svg" alt="{{ config('app.name', 'MatterLynk') }}" class="size-5" />
        </x-slot>
    </flux:brand>
@endif
