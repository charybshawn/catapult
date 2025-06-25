<?php

namespace App\Filament\Traits;

use Filament\Tables;
use Filament\Forms;

trait HasConsistentSlideOvers
{
    /**
     * Configure a consistent View action with slideover
     */
    protected static function makeViewAction(array $config = []): Tables\Actions\ViewAction
    {
        $defaults = [
            'tooltip' => 'View details',
            'modalWidth' => '3xl',
            'heading' => 'View Details',
            'description' => null,
            'icon' => 'heroicon-o-eye',
            'footerActions' => [],
        ];

        $config = array_merge($defaults, $config);

        $action = Tables\Actions\ViewAction::make()
            ->tooltip($config['tooltip'])
            ->modalWidth($config['modalWidth'])
            ->modalHeading($config['heading'])
            ->slideOver()
            ->modalIcon($config['icon']);

        // Add dynamic description if provided
        if ($config['description']) {
            if (is_callable($config['description'])) {
                $action->modalDescription($config['description']);
            } else {
                $action->modalDescription($config['description']);
            }
        }

        // Add extra footer actions if provided
        if (!empty($config['footerActions'])) {
            $action->extraModalFooterActions($config['footerActions']);
        }

        return $action;
    }

    /**
     * Configure a consistent Edit action with slideover
     */
    protected static function makeEditAction(array $config = []): Tables\Actions\EditAction
    {
        $defaults = [
            'tooltip' => 'Edit record',
            'modalWidth' => '3xl',
            'heading' => 'Edit Record',
            'description' => null,
            'icon' => 'heroicon-o-pencil-square',
        ];

        $config = array_merge($defaults, $config);

        $action = Tables\Actions\EditAction::make()
            ->tooltip($config['tooltip'])
            ->modalWidth($config['modalWidth'])
            ->modalHeading($config['heading'])
            ->slideOver()
            ->modalIcon($config['icon']);

        // Add dynamic description if provided
        if ($config['description']) {
            if (is_callable($config['description'])) {
                $action->modalDescription($config['description']);
            } else {
                $action->modalDescription($config['description']);
            }
        }

        return $action;
    }

    /**
     * Configure a consistent Create action with slideover
     */
    protected static function makeCreateAction(array $config = []): Tables\Actions\CreateAction
    {
        $defaults = [
            'label' => 'Create New',
            'tooltip' => 'Create a new record',
            'modalWidth' => '3xl',
            'heading' => 'Create New Record',
            'description' => 'Add a new record to the system',
            'icon' => 'heroicon-o-plus',
        ];

        $config = array_merge($defaults, $config);

        return Tables\Actions\CreateAction::make()
            ->label($config['label'])
            ->tooltip($config['tooltip'])
            ->modalWidth($config['modalWidth'])
            ->modalHeading($config['heading'])
            ->modalDescription($config['description'])
            ->slideOver()
            ->modalIcon($config['icon']);
    }

    /**
     * Configure a standard set of table actions with consistent styling
     */
    protected static function getStandardTableActions(array $config = []): array
    {
        $defaults = [
            'view' => true,
            'edit' => true,
            'delete' => true,
            'viewConfig' => [],
            'editConfig' => [],
            'deleteConfig' => [],
        ];

        $config = array_merge($defaults, $config);
        $actions = [];

        if ($config['view']) {
            $actions[] = static::makeViewAction($config['viewConfig']);
        }

        if ($config['edit']) {
            $actions[] = static::makeEditAction($config['editConfig']);
        }

        if ($config['delete']) {
            $deleteConfig = array_merge([
                'tooltip' => 'Delete record'
            ], $config['deleteConfig']);
            
            $deleteAction = Tables\Actions\DeleteAction::make()
                ->tooltip($deleteConfig['tooltip']);
            
            $actions[] = $deleteAction;
        }

        return $actions;
    }

    /**
     * Configure standard header actions with create button
     */
    protected static function getStandardHeaderActions(array $config = []): array
    {
        $defaults = [
            'create' => true,
            'createConfig' => [],
            'extraActions' => [],
        ];

        $config = array_merge($defaults, $config);
        $actions = [];

        if ($config['create']) {
            $actions[] = static::makeCreateAction($config['createConfig']);
        }

        // Add any extra header actions
        if (!empty($config['extraActions'])) {
            $actions = array_merge($actions, $config['extraActions']);
        }

        return $actions;
    }

    /**
     * Create quick action buttons for modal footers
     */
    protected static function makeQuickAction(string $name, array $config = []): Tables\Actions\Action
    {
        $defaults = [
            'label' => ucfirst($name),
            'icon' => 'heroicon-o-bolt',
            'color' => 'gray',
            'action' => null,
            'url' => null,
        ];

        $config = array_merge($defaults, $config);

        $action = Tables\Actions\Action::make($name)
            ->label($config['label'])
            ->icon($config['icon'])
            ->color($config['color']);

        if ($config['action']) {
            $action->action($config['action']);
        }

        if ($config['url']) {
            $action->url($config['url']);
        }

        return $action;
    }
}