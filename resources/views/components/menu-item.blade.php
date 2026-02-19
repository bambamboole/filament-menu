@props([
    'item' => [],
])

<li>
    <a href="{{ $item['url'] ?? '#' }}" @if(($item['target'] ?? '_self') === '_blank') target="_blank" rel="noopener noreferrer" @endif>
        {{ $item['label'] }}
    </a>

    @if(! empty($item['children']))
        <ul>
            @foreach($item['children'] as $child)
                <x-filament-menu::menu-item :item="$child" />
            @endforeach
        </ul>
    @endif
</li>
