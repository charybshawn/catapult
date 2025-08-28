<?php

namespace App\Filament\Traits;

use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Forms;

/**
 * Has Consistent Slide Overs Trait
 * 
 * Standardized slide-over panel configurations for agricultural Filament resources.
 * Provides consistent UI patterns for viewing, editing, and creating agricultural
 * entities with proper sizing, styling, and contextual actions.
 * 
 * @filament_trait Reusable slide-over panel configurations
 * @agricultural_use Consistent slide-over UI for agricultural resource management
 * @ui_consistency Standardized slide-over patterns across agricultural workflows
 * @user_experience Optimized panel sizing and behavior for agricultural data entry
 * 
 * Key features:
 * - Standardized slide-over configurations for agricultural resources
 * - Consistent modal sizing and styling for agricultural data
 * - Configurable actions and behaviors for agricultural workflows
 * - Quick action patterns for agricultural entity relationships
 * - Agricultural-appropriate tooltips and helper text
 * 
 * @package App\Filament\Traits
 * @author Shawn
 * @since 2024
 */
trait HasConsistentSlideOvers
{
    /**
     * Configure a consistent View action with slide-over for agricultural resources.
     * 
     * @agricultural_context Standardized view action for agricultural entities with contextual information
     * @param array $config Configuration overrides for view action customization
     * @return ViewAction Configured view action with slide-over panel and agricultural UI patterns
     * @ui_pattern 3xl modal width optimized for agricultural data display
     */
    protected static function makeViewAction(array $config = []): ViewAction
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

        $action = ViewAction::make()
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
     * Configure a consistent Edit action with slide-over for agricultural resources.
     * 
     * @agricultural_context Standardized edit action for agricultural entities with form optimization
     * @param array $config Configuration overrides for edit action customization
     * @return EditAction Configured edit action with slide-over panel optimized for agricultural forms
     * @ui_pattern 3xl modal width suitable for agricultural data entry forms
     */
    protected static function makeEditAction(array $config = []): EditAction
    {
        $defaults = [
            'tooltip' => 'Edit record',
            'modalWidth' => '3xl',
            'heading' => 'Edit Record',
            'description' => null,
            'icon' => 'heroicon-o-pencil-square',
        ];

        $config = array_merge($defaults, $config);

        $action = EditAction::make()
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
    protected static function makeCreateAction(array $config = []): CreateAction
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

        return CreateAction::make()
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
            
            $deleteAction = DeleteAction::make()
                ->tooltip($deleteConfig['tooltip']);
            
            $actions[] = $deleteAction;
        }

        return $actions;
    }

    /**
     * Configure standard header actions with create button for agricultural resources.
     * 
     * @agricultural_context Standard header actions for agricultural resource list pages
     * @param array $config Configuration for header actions and create button
     * @return array Header actions array including create button and extra agricultural actions
     * @workflow_pattern Consistent header action patterns across agricultural resources
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
     * Create quick action buttons for modal footers in agricultural workflows.
     * 
     * @agricultural_context Quick actions for agricultural entity relationships and workflows
     * @param string $name Action name identifier
     * @param array $config Action configuration including label, icon, color, and behavior
     * @return Action Configured quick action for agricultural slide-over footers
     * @use_cases View related orders, check inventory, contact suppliers, start crops
     */
    protected static function makeQuickAction(string $name, array $config = []): Action
    {
        $defaults = [
            'label' => ucfirst($name),
            'icon' => 'heroicon-o-bolt',
            'color' => 'gray',
            'action' => null,
            'url' => null,
        ];

        $config = array_merge($defaults, $config);

        $action = Action::make($name)
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