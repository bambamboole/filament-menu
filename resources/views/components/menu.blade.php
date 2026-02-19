@props([
    'location' => null,
    'slug' => null,
])

@php
    $menu = null;

    if ($location) {
        $menu = \Bambamboole\FilamentMenu\Models\Menu::findByLocation($location);
    } elseif ($slug) {
        $menu = \Bambamboole\FilamentMenu\Models\Menu::findBySlug($slug);
    }

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
