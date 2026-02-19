<?php

namespace Bambamboole\FilamentMenu\Filament\Resources\MenuResource\Pages;

use Bambamboole\FilamentMenu\Contracts\Linkable;
use Bambamboole\FilamentMenu\Filament\Resources\MenuResource;
use Bambamboole\FilamentMenu\FilamentMenuPlugin;
use Bambamboole\FilamentMenu\Models\MenuItem;
use Bambamboole\FilamentMenu\Models\MenuLocation;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Validator;

/**
 * @property \Bambamboole\FilamentMenu\Models\Menu $record
 */
class EditMenu extends EditRecord
{
    protected static string $resource = MenuResource::class;

    /** @var array{label: string, url: string, target: string, type: string, linkable_id: ?int} */
    public array $menuItemData = [
        'label' => '',
        'url' => '',
        'target' => '_self',
        'type' => 'link',
        'linkable_id' => null,
    ];

    public ?int $editingItemId = null;

    public ?string $assignedLocation = null;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        $location = $this->record->locations()->first();
        $this->assignedLocation = $location?->location;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
                $this->getLocationSection(),
                Grid::make(2)
                    ->schema([
                        $this->getAddItemSection(),
                        $this->getTreeSection(),
                    ]),
            ]);
    }

    protected function getLocationSection(): Section
    {
        $locations = FilamentMenuPlugin::get()->getLocations();

        return Section::make(__('filament-menu::menu.edit.location.title'))
            ->schema([
                Select::make('assignedLocation')
                    ->label(__('filament-menu::menu.edit.location.label'))
                    ->options(array_combine($locations, array_map(ucfirst(...), $locations)))
                    ->placeholder(__('filament-menu::menu.edit.location.placeholder'))
                    ->reactive()
                    ->afterStateUpdated(function (?string $state): void {
                        $this->record->locations()->delete();

                        if ($state !== null) {
                            MenuLocation::query()
                                ->where('location', $state)
                                ->delete();

                            $this->record->locations()->create([
                                'location' => $state,
                            ]);
                        }
                    }),
            ])
            ->visible(fn (): bool => $locations !== []);
    }

    protected function getAddItemSection(): Section
    {
        $linkables = FilamentMenuPlugin::get()->getLinkables();

        $typeOptions = ['link' => __('filament-menu::menu.edit.item.type_link')];

        foreach ($linkables as $linkable) {
            $typeOptions[$linkable] = $linkable::getLinkableLabel();
        }

        return Section::make(fn (): string => $this->editingItemId
                ? __('filament-menu::menu.edit.item.title_edit')
                : __('filament-menu::menu.edit.item.title_add'))
            ->schema([
                TextInput::make('menuItemData.label')
                    ->label(__('filament-menu::menu.edit.item.label'))
                    ->required(),

                Select::make('menuItemData.type')
                    ->label(__('filament-menu::menu.edit.item.type'))
                    ->options($typeOptions)
                    ->default('link')
                    ->live(),

                TextInput::make('menuItemData.url')
                    ->label(__('filament-menu::menu.edit.item.url'))
                    ->url()
                    ->visible(fn (Get $get): bool => $get('menuItemData.type') === 'link'),

                Select::make('menuItemData.linkable_id')
                    ->label(__('filament-menu::menu.edit.item.record'))
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search, Get $get): array {
                        $type = $get('menuItemData.type');

                        if ($type === 'link' || ! is_a($type, Linkable::class, true)) {
                            return [];
                        }

                        return $type::getLinkableSearchResults($search);
                    })
                    ->getOptionLabelUsing(function ($value, Get $get): ?string {
                        $type = $get('menuItemData.type');

                        if (! is_a($type, Linkable::class, true)) {
                            return null;
                        }

                        $results = $type::getLinkableSearchResults('');

                        return $results[$value] ?? null;
                    })
                    ->visible(fn (Get $get): bool => $get('menuItemData.type') !== 'link'),

                Select::make('menuItemData.target')
                    ->label(__('filament-menu::menu.edit.item.target'))
                    ->options([
                        '_self' => __('filament-menu::menu.edit.item.target_self'),
                        '_blank' => __('filament-menu::menu.edit.item.target_blank'),
                    ])
                    ->default('_self'),

                \Filament\Schemas\Components\Actions::make([
                    Action::make('addMenuItem')
                        ->label(fn (): string => $this->editingItemId
                            ? __('filament-menu::menu.edit.item.button_update')
                            : __('filament-menu::menu.edit.item.button_add'))
                        ->action('addMenuItem'),

                    Action::make('cancelEdit')
                        ->label(__('filament-menu::menu.edit.item.button_cancel'))
                        ->color('gray')
                        ->visible(fn (): bool => $this->editingItemId !== null)
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

    public function addMenuItem(): void
    {
        $linkables = FilamentMenuPlugin::get()->getLinkables();
        $validTypes = implode(',', ['link', ...$linkables]);

        $validator = Validator::make($this->menuItemData, [
            'label' => 'required|string|max:255',
            'url' => 'nullable|string|max:255',
            'target' => 'nullable|string|in:_self,_blank',
            'type' => "required|string|in:{$validTypes}",
            'linkable_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->addError('menuItemData.label', $error);
            }

            return;
        }

        $data = $validator->validated();

        $isLinkable = $data['type'] !== 'link' && in_array($data['type'], $linkables);

        $attributes = [
            'label' => $data['label'],
            'target' => $data['target'],
            'type' => $data['type'],
            'url' => $isLinkable ? null : $data['url'],
            'linkable_type' => $isLinkable ? $data['type'] : null,
            'linkable_id' => $isLinkable ? $data['linkable_id'] : null,
        ];

        if ($this->editingItemId) {
            $item = MenuItem::find($this->editingItemId);

            if ($item) {
                $item->update($attributes);
            }
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
        $item = MenuItem::find($id);

        if (! $item) {
            return;
        }

        $this->editingItemId = $id;
        $this->menuItemData = [
            'label' => $item->label,
            'url' => $item->url ?? '',
            'target' => $item->target ?? '_self',
            'type' => $item->linkable_type ?? 'link',
            'linkable_id' => $item->linkable_id,
        ];
    }

    public function deleteItem(int $id): void
    {
        MenuItem::destroy($id);

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

    private function resetItemForm(): void
    {
        $this->editingItemId = null;
        $this->menuItemData = [
            'label' => '',
            'url' => '',
            'target' => '_self',
            'type' => 'link',
            'linkable_id' => null,
        ];
    }

    /**
     * @param  array<int, array{id: int, children: array<int, mixed>}>  $items
     */
    private function persistTree(array $items, ?int $parentId, int &$order): void
    {
        foreach ($items as $item) {
            MenuItem::where('id', $item['id'])->update([
                'parent_id' => $parentId,
                'sort_order' => $order++,
            ]);

            if (! empty($item['children'])) {
                $this->persistTree($item['children'], $item['id'], $order);
            }
        }
    }
}
