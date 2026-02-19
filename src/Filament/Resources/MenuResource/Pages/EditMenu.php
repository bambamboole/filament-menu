<?php

namespace Bambamboole\FilamentMenu\Filament\Resources\MenuResource\Pages;

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
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Validator;

/**
 * @property \Bambamboole\FilamentMenu\Models\Menu $record
 */
class EditMenu extends EditRecord
{
    protected static string $resource = MenuResource::class;

    /** @var array{label: string, url: string, target: string, type: string} */
    public array $menuItemData = [
        'label' => '',
        'url' => '',
        'target' => '_self',
        'type' => 'link',
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

        return Section::make('Location')
            ->schema([
                Select::make('assignedLocation')
                    ->label('Menu Location')
                    ->options(array_combine($locations, array_map(ucfirst(...), $locations)))
                    ->placeholder('No location assigned')
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
        return Section::make(fn (): string => $this->editingItemId ? 'Edit Menu Item' : 'Add Menu Item')
            ->schema([
                TextInput::make('menuItemData.label')
                    ->label('Label')
                    ->required(),

                TextInput::make('menuItemData.url')
                    ->label('URL')
                    ->url(),

                Select::make('menuItemData.target')
                    ->label('Target')
                    ->options([
                        '_self' => 'Same Window',
                        '_blank' => 'New Window',
                    ])
                    ->default('_self'),

                Select::make('menuItemData.type')
                    ->label('Type')
                    ->options([
                        'link' => 'Link',
                        'page' => 'Page',
                    ])
                    ->default('link'),

                \Filament\Schemas\Components\Actions::make([
                    Action::make('addMenuItem')
                        ->label(fn (): string => $this->editingItemId ? 'Update Item' : 'Add Item')
                        ->action('addMenuItem'),

                    Action::make('cancelEdit')
                        ->label('Cancel')
                        ->color('gray')
                        ->visible(fn (): bool => $this->editingItemId !== null)
                        ->action('cancelEdit'),
                ]),
            ]);
    }

    protected function getTreeSection(): Section
    {
        return Section::make('Menu Structure')
            ->schema([
                View::make('filament-menu::menu-tree'),
            ]);
    }

    public function addMenuItem(): void
    {
        $validator = Validator::make($this->menuItemData, [
            'label' => 'required|string|max:255',
            'url' => 'nullable|string|max:255',
            'target' => 'nullable|string|in:_self,_blank',
            'type' => 'required|string|in:link,page',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->addError('menuItemData.label', $error);
            }

            return;
        }

        $data = $validator->validated();

        if ($this->editingItemId) {
            $item = MenuItem::find($this->editingItemId);

            if ($item) {
                $item->update($data);
            }
        } else {
            $maxSort = $this->record->rootItems()->max('sort_order') ?? -1;

            $this->record->items()->create([
                ...$data,
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
            'type' => $item->type,
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
