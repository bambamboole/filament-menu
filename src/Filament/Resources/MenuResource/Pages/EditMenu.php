<?php

namespace Bambamboole\FilamentMenu\Filament\Resources\MenuResource\Pages;

use Bambamboole\FilamentMenu\Filament\Resources\MenuResource;
use Bambamboole\FilamentMenu\FilamentMenuPlugin;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

/**
 * @property \Bambamboole\FilamentMenu\Models\Menu $record
 */
class EditMenu extends EditRecord
{
    protected static string $resource = MenuResource::class;

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
                Grid::make(2)
                    ->schema([
                        $this->getAddItemsSection(),
                        $this->getTreeSection(),
                    ]),
            ]);
    }

    protected function getAddItemsSection(): Section
    {
        $actions = [];

        foreach (FilamentMenuPlugin::get()->getLinkables() as $linkable) {
            $actions[] = $this->makeAddLinkableAction($linkable);
        }

        $actions[] = $this->makeAddCustomLinkAction();

        return Section::make(__('filament-menu::menu.edit.add_items.title'))
            ->schema([
                Actions::make($actions)->key('add-menu-item-actions'),
            ]);
    }

    protected function getTreeSection(): Section
    {
        return Section::make(__('filament-menu::menu.edit.structure.title'))
            ->schema([
                View::make('filament-menu::menu-tree'),
            ]);
    }

    private function makeAddLinkableAction(string $linkable): Action
    {
        $key = self::linkableKey($linkable);
        $label = $linkable::getLinkableLabel();

        return Action::make("addLinkable_{$key}")
            ->label(__('filament-menu::menu.edit.linked.title_add', ['type' => $label]))
            ->schema([
                Select::make('linkable_id')
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
                    ->afterStateUpdated(function (?int $state, callable $set) use ($linkable): void {
                        if ($state === null) {
                            return;
                        }

                        $record = $linkable::find($state);

                        if ($record) {
                            $set('label', $record->{$linkable::getNameColumn()});
                        }
                    }),

                TextInput::make('label')
                    ->label(__('filament-menu::menu.edit.linked.label'))
                    ->required()
                    ->maxLength(255),

                Select::make('target')
                    ->label(__('filament-menu::menu.edit.linked.target'))
                    ->options([
                        '_self' => __('filament-menu::menu.edit.item.target_self'),
                        '_blank' => __('filament-menu::menu.edit.item.target_blank'),
                    ])
                    ->default('_self'),
            ])
            ->action(function (array $data) use ($linkable): void {
                $maxSort = $this->record->rootItems()->max('sort_order') ?? -1;

                $this->record->items()->create([
                    'label' => $data['label'],
                    'target' => $data['target'],
                    'url' => null,
                    'linkable_type' => $linkable,
                    'linkable_id' => $data['linkable_id'],
                    'sort_order' => $maxSort + 1,
                ]);
            });
    }

    private function makeAddCustomLinkAction(): Action
    {
        return Action::make('addCustomLink')
            ->label(__('filament-menu::menu.edit.custom.title_add'))
            ->schema([
                TextInput::make('label')
                    ->label(__('filament-menu::menu.edit.custom.label'))
                    ->required()
                    ->maxLength(255),

                TextInput::make('url')
                    ->label(__('filament-menu::menu.edit.custom.url'))
                    ->required()
                    ->maxLength(255),

                Select::make('target')
                    ->label(__('filament-menu::menu.edit.custom.target'))
                    ->options([
                        '_self' => __('filament-menu::menu.edit.item.target_self'),
                        '_blank' => __('filament-menu::menu.edit.item.target_blank'),
                    ])
                    ->default('_self'),
            ])
            ->action(function (array $data): void {
                $maxSort = $this->record->rootItems()->max('sort_order') ?? -1;

                $this->record->items()->create([
                    'label' => $data['label'],
                    'target' => $data['target'],
                    'url' => $data['url'],
                    'sort_order' => $maxSort + 1,
                ]);
            });
    }

    private function makeEditItemAction(): Action
    {
        $linkables = FilamentMenuPlugin::get()->getLinkables();

        return Action::make('editItem')
            ->mountUsing(function (Schema $form, array $arguments): void {
                $item = $this->record->items()->find($arguments['itemId']);

                if (! $item) {
                    return;
                }

                $data = [
                    'label' => $item->label,
                    'target' => $item->target ?? '_self',
                    'item_type' => $item->linkable_type ? 'linkable' : 'custom',
                ];

                if ($item->linkable_type) {
                    $data['linkable_type'] = $item->linkable_type;
                    $data['linkable_id'] = $item->linkable_id;
                } else {
                    $data['url'] = $item->url ?? '';
                }

                $form->fill($data);
            })
            ->schema([
                Hidden::make('item_type'),
                Hidden::make('linkable_type'),

                ...array_map(
                    fn (string $linkable): Select => Select::make('linkable_id')
                        ->label(__('filament-menu::menu.edit.linked.record'))
                        ->searchable()
                        ->preload()
                        ->getSearchResultsUsing(fn (string $search): array => $linkable::getLinkableSearchResults($search))
                        ->getOptionLabelUsing(function ($value) use ($linkable): ?string {
                            $record = $linkable::find($value);

                            return $record?->{$linkable::getNameColumn()};
                        })
                        ->options($linkable::latest()->limit(10)->pluck($linkable::getNameColumn(), 'id'))
                        ->visible(fn (Get $get): bool => $get('item_type') === 'linkable' && $get('linkable_type') === $linkable),
                    $linkables
                ),

                TextInput::make('url')
                    ->label(__('filament-menu::menu.edit.custom.url'))
                    ->maxLength(255)
                    ->visible(fn (Get $get): bool => $get('item_type') === 'custom'),

                TextInput::make('label')
                    ->label(__('filament-menu::menu.edit.linked.label'))
                    ->required()
                    ->maxLength(255),

                Select::make('target')
                    ->label(__('filament-menu::menu.edit.linked.target'))
                    ->options([
                        '_self' => __('filament-menu::menu.edit.item.target_self'),
                        '_blank' => __('filament-menu::menu.edit.item.target_blank'),
                    ]),
            ])
            ->action(function (array $data, array $arguments): void {
                $item = $this->record->items()->find($arguments['itemId']);

                if (! $item) {
                    return;
                }

                $attributes = [
                    'label' => $data['label'],
                    'target' => $data['target'],
                ];

                if ($data['item_type'] === 'custom') {
                    $attributes['url'] = $data['url'];
                } else {
                    $attributes['linkable_id'] = $data['linkable_id'];
                }

                $item->update($attributes);
            });
    }

    private function makeDeleteItemAction(): Action
    {
        return Action::make('deleteItem')
            ->requiresConfirmation()
            ->color('danger')
            ->action(function (array $arguments): void {
                $this->record->items()->where('id', $arguments['itemId'])->delete();
            });
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
            $this->makeEditItemAction(),
            $this->makeDeleteItemAction(),
            DeleteAction::make(),
        ];
    }

    private static function linkableKey(string $class): string
    {
        return str_replace('\\', '_', strtolower($class));
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
