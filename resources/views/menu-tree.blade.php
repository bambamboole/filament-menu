<div
    x-data="{
        getDataStructure(parentNode) {
            const items = Array.from(parentNode.children).filter((item) => {
                return item.classList.contains('item');
            });

            return items.map((item) => {
                const id = parseInt(item.getAttribute('data-id'));
                const nestedContainer = item.querySelector(':scope > .nested');
                const children = nestedContainer ? this.getDataStructure(nestedContainer) : [];

                return { id, children };
            });
        }
    }"
>
    @if($this->record->items()->count() > 0)
        <div
            id="parentNested"
            class="nested"
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
            @foreach($this->record->rootItems()->with('children.children.children')->get() as $item)
                @include('filament-menu::menu-item', ['item' => $item])
            @endforeach
        </div>
    @else
        <div class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center">
            No menu items yet. Use the form on the left to add items.
        </div>
    @endif
</div>
