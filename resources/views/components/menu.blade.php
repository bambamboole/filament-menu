@props([
    'location',
    'locale' => null,
])

@php
    $menu = \Bambamboole\FilamentMenu\Models\Menu::findByLocation($location, $locale);
    $tree = $menu?->getTree() ?? [];
@endphp

@if(count($tree) > 0)
    <nav {{ $attributes }}>
        <ul>
            @foreach($tree as $item)
                <x-filament-menu::menu-item :item="$item" />
            @endforeach
        </ul>
    </nav>
@endif
