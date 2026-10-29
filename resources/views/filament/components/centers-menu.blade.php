@php
    $currentPanel = filament()->getCurrentPanel()?->getId();
@endphp

<x-filament::dropdown placement="bottom-end" teleport>
    <x-slot name="trigger">
        <button type="button" class="agora-centers-menu-trigger">
            <x-filament::icon icon="heroicon-o-squares-2x2" class="h-5 w-5" />
            <span>Centros</span>
            <x-filament::icon icon="heroicon-m-chevron-down" class="h-4 w-4" />
        </button>
    </x-slot>

    <x-filament::dropdown.list>
        @foreach (\App\Support\ExcellenceCenters::active() as $center)
            <x-filament::dropdown.list.item
                :color="$currentPanel === $center['panel'] ? 'primary' : 'gray'"
                :href="($center['url'])()"
                :icon="$center['icon']"
                tag="a"
            >
                <span class="font-semibold">{{ $center['name'] }}</span>
                <span class="block text-xs text-gray-500">{{ $center['subtitle'] }}</span>
            </x-filament::dropdown.list.item>
        @endforeach
    </x-filament::dropdown.list>
</x-filament::dropdown>
