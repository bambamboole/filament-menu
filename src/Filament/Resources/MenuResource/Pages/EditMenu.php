<?php

namespace Bambamboole\FilamentMenu\Filament\Resources\MenuResource\Pages;

use Bambamboole\FilamentMenu\Filament\Resources\MenuResource;
use Bambamboole\FilamentMenu\FilamentMenuPlugin;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Validator;

/**
 * @property \Bambamboole\FilamentMenu\Models\Menu $record
 */
class EditMenu extends EditRecord
{
    protected static string $resource = MenuResource::class;

    /** @var array<string, array{linkable_id: ?int, label: string, target: string}> */
    public array $linkableData = [];

    /** @var array{label: string, url: string, target: string} */
    public array $customItemData = [
        'label' => '',
        'url' => '',
        'target' => '_self',
    ];

    public ?int $editingItemId = null;

    public string $editingForm = '';

    public function mount(int | string $record): void
    {
        parent::mount($record);

        $this->initLinkableData();
    }

    public function content(Schema $schema): Schema
    {
        $linkableSections = [];

        foreach (FilamentMenuPlugin::get()->getLinkables() as $linkable) {
            $linkableSections[] = $this->getLinkableSection($linkable);
        }

        return $schema
            ->components([
                $this->getFormContentComponent(),
                Grid::make(2)
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                ...$linkableSections,
                                $this->getCustomItemSection(),
                            ]),
                        $this->getTreeSection(),
                    ]),
            ]);
    }

    protected function getLinkableSection(string $linkable): Section
    {
        $key = self::linkableKey($linkable);
        $label = $linkable::getLinkableLabel();

        return Section::make(fn (): string => $this->editingForm === $key
                ? __('filament-menu::menu.edit.linked.title_edit', ['type' => $label])
                : __('filament-menu::menu.edit.linked.title_add', ['type' => $label]))
            ->collapsible()
            ->collapsed(fn (): bool => $this->editingForm !== $key)
            ->schema([
                Select::make("linkableData.{$key}.linkable_id")
                    ->label(__('filament-menu::menu.edit.linked.record'))
                    ->searchable()
                    ->required()
                    ->preload()
                    ->getSearchResultsUsing(fn (string $search): array => $linkable::getLinkableSearchResults($search))
                    ->getOptionLabelUsing(function ($value) use ($linkable): ?string {
                        $record = $linkable::find($value);

                        return $record?->{$linkable::getNameColumn()};
                    })
                    ->options($linkable::latest()->limit(10)->pluck($linkable::getNameColumn(), 'id'))
                    ->live()
                    ->afterStateUpdated(function (?int $state, Set $set) use ($linkable, $key): void {
                        if ($state === null) {
                            return;
                        }

                        $record = $linkable::find($state);

                        if ($record) {
                            $set("linkableData.{$key}.label", $record->{$linkable::getNameColumn()});
                        }
                    }),

                TextInput::make("linkableData.{$key}.label")
                    ->label(__('filament-menu::menu.edit.linked.label'))
                    ->required(),

                Select::make("linkableData.{$key}.target")
                    ->label(__('filament-menu::menu.edit.linked.target'))
                    ->options([
                        '_self' => __('filament-menu::menu.edit.item.target_self'),
                        '_blank' => __('filament-menu::menu.edit.item.target_blank'),
                    ])
                    ->default('_self'),

                \Filament\Schemas\Components\Actions::make([
                    Action::make("addLinkable_{$key}")
                        ->label(fn (): string => $this->editingForm === $key
                            ? __('filament-menu::menu.edit.item.button_update')
                            : __('filament-menu::menu.edit.item.button_add'))
                        ->action(fn () => $this->addLinkableItem($linkable)),

                    Action::make("cancelLinkable_{$key}")
                        ->label(__('filament-menu::menu.edit.item.button_cancel'))
                        ->color('gray')
                        ->visible(fn (): bool => $this->editingForm === $key)
                        ->action('cancelEdit'),
                ]),
            ]);
    }

    protected function getCustomItemSection(): Section
    {
        return Section::make(fn (): string => $this->editingForm === 'custom'
                ? __('filament-menu::menu.edit.custom.title_edit')
                : __('filament-menu::menu.edit.custom.title_add'))
            ->collapsible()
            ->collapsed(fn (): bool => $this->editingForm !== 'custom')
            ->schema([
                TextInput::make('customItemData.label')
                    ->label(__('filament-menu::menu.edit.custom.label'))
                    ->required(),

                TextInput::make('customItemData.url')
                    ->label(__('filament-menu::menu.edit.custom.url'))
                    ->required(),

                Select::make('customItemData.target')
                    ->label(__('filament-menu::menu.edit.custom.target'))
                    ->options([
                        '_self' => __('filament-menu::menu.edit.item.target_self'),
                        '_blank' => __('filament-menu::menu.edit.item.target_blank'),
                    ])
                    ->default('_self'),

                \Filament\Schemas\Components\Actions::make([
                    Action::make('addCustomItem')
                        ->label(fn (): string => $this->editingForm === 'custom'
                            ? __('filament-menu::menu.edit.item.button_update')
                            : __('filament-menu::menu.edit.item.button_add'))
                        ->action('addCustomItem'),

                    Action::make('cancelCustomEdit')
                        ->label(__('filament-menu::menu.edit.item.button_cancel'))
                        ->color('gray')
                        ->visible(fn (): bool => $this->editingForm === 'custom')
                        ->action('cancelEdit'),
                ]),
            ]);
    }

    protected function getTreeSection(): Section
    {
        return Section::make(__('filament-menu::menu.edit.structure.title'))
            ->schema([
                View::make('filament-menu::menu-tree'),
            ]);
    }

    public function addLinkableItem(string $linkableType): void
    {
        $key = self::linkableKey($linkableType);
        $data = $this->linkableData[$key] ?? null;

        if (! $data) {
            return;
        }

        $validator = Validator::make($data, [
            'linkable_id' => 'required|integer',
            'label' => 'required|string|max:255',
            'target' => 'nullable|string|in:_self,_blank',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->addError("linkableData.{$key}.label", $error);
            }

            return;
        }

        $validated = $validator->validated();

        $attributes = [
            'label' => $validated['label'],
            'target' => $validated['target'],
            'type' => $linkableType,
            'url' => null,
            'linkable_type' => $linkableType,
            'linkable_id' => $validated['linkable_id'],
        ];

        if ($this->editingItemId) {
            $this->record->items()->where('id', $this->editingItemId)->update($attributes);
        } else {
            $maxSort = $this->record->rootItems()->max('sort_order') ?? -1;

            $this->record->items()->create([
                ...$attributes,
                'sort_order' => $maxSort + 1,
            ]);
        }

        $this->resetItemForm();
    }

    public function addCustomItem(): void
    {
        $validator = Validator::make($this->customItemData, [
            'label' => 'required|string|max:255',
            'url' => 'required|string|max:255',
            'target' => 'nullable|string|in:_self,_blank',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->addError('customItemData.label', $error);
            }

            return;
        }

        $data = $validator->validated();

        $attributes = [
            'label' => $data['label'],
            'target' => $data['target'],
            'type' => 'link',
            'url' => $data['url'],
            'linkable_type' => null,
            'linkable_id' => null,
        ];

        if ($this->editingItemId) {
            $this->record->items()->where('id', $this->editingItemId)->update($attributes);
        } else {
            $maxSort = $this->record->rootItems()->max('sort_order') ?? -1;

            $this->record->items()->create([
                ...$attributes,
                'sort_order' => $maxSort + 1,
            ]);
        }

        $this->resetItemForm();
    }

    public function editItem(int $id): void
    {
        $item = $this->record->items()->find($id);

        if (! $item) {
            return;
        }

        $this->editingItemId = $id;

        if ($item->linkable_type) {
            $key = self::linkableKey($item->linkable_type);
            $this->editingForm = $key;
            $this->linkableData[$key] = [
                'linkable_id' => $item->linkable_id,
                'label' => $item->label,
                'target' => $item->target ?? '_self',
            ];
        } else {
            $this->editingForm = 'custom';
            $this->customItemData = [
                'label' => $item->label,
                'url' => $item->url ?? '',
                'target' => $item->target ?? '_self',
            ];
        }
    }

    public function deleteItem(int $id): void
    {
        $this->record->items()->where('id', $id)->delete();

        if ($this->editingItemId === $id) {
            $this->resetItemForm();
        }
    }

    public function cancelEdit(): void
    {
        $this->resetItemForm();
    }

    /**
     * @param  array<int, array{id: int, children: array<int, mixed>}>  $tree
     */
    public function reorderTree(array $tree): void
    {
        $order = 0;
        $this->persistTree($tree, null, $order);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    private static function linkableKey(string $class): string
    {
        return str_replace('\\', '_', strtolower($class));
    }

    private function initLinkableData(): void
    {
        foreach (FilamentMenuPlugin::get()->getLinkables() as $linkable) {
            $key = self::linkableKey($linkable);
            $this->linkableData[$key] = [
                'linkable_id' => null,
                'label' => '',
                'target' => '_self',
            ];
        }
    }

    private function resetItemForm(): void
    {
        $this->editingItemId = null;
        $this->editingForm = '';
        $this->initLinkableData();
        $this->customItemData = [
            'label' => '',
            'url' => '',
            'target' => '_self',
        ];
    }

    /**
     * @param  array<int, array{id: int, children: array<int, mixed>}>  $items
     */
    private function persistTree(array $items, ?int $parentId, int &$order): void
    {
        foreach ($items as $item) {
            $this->record->items()->where('id', $item['id'])->update([
                'parent_id' => $parentId,
                'sort_order' => $order++,
            ]);

            if (! empty($item['children'])) {
                $this->persistTree($item['children'], $item['id'], $order);
            }
        }
    }
}
