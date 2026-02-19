<div class="item" data-id="{{ $item->id }}" wire:key="menu-item-{{ $item->id }}">
    <div class="flex justify-between mb-1 items-center rounded-lg bg-white border border-gray-200 shadow-sm pr-2 dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center">
            <div class="border-r border-gray-200 dark:border-gray-700 cursor-grab">
                <x-filament::icon
                    icon="heroicon-m-bars-2"
                    class="w-5 h-5 m-2 handle text-gray-400"
                />
            </div>
            <div class="ml-2 flex items-center gap-2">
                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->label }}</span>
                @if($item->url)
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $item->url }}</span>
                @endif
            </div>
        </div>
        <div class="flex gap-1 items-center">
            <button
                type="button"
                class="text-gray-400 hover:text-primary-500 p-1"
                wire:click="editItem({{ $item->id }})"
            >
                <x-filament::icon icon="heroicon-m-pencil-square" class="h-4 w-4" />
            </button>
            <button
                type="button"
                class="text-gray-400 hover:text-danger-500 p-1"
                wire:click="deleteItem({{ $item->id }})"
            >
                <x-filament::icon icon="heroicon-m-trash" class="h-4 w-4" />
            </button>
        </div>
    </div>

    <div
        class="nested ml-6"
        data-id="{{ $item->id }}"
        x-data="{
            init() {
                new Sortable(this.$el, {
                    handle: '.handle',
                    group: 'nested',
                    animation: 150,
                    fallbackOnBody: true,
                    swapThreshold: 0.65,
                    onEnd: (evt) => {
                        const data = this.getDataStructure(document.getElementById('parentNested'));
                        $wire.call('reorderTree', data);
                    }
                })
            },
        }"
    >
        @foreach($item->children as $child)
            @include('filament-menu::menu-item', ['item' => $child])
        @endforeach
    </div>
</div>
