# Tasks v002

## Add Dynamic Recipe Creation to ProductMix Forms

### Current State Analysis
- [X] ProductMix form has recipe selection dropdown that shows existing active recipes
- [X] Recipe creation is currently separate in RecipeResource
- [X] The recipe field is optional with a placeholder "Use default recipe"
- [X] ProductMix components store `recipe_id` in pivot table

### Implementation Plan

#### 1. Create Recipe Creation Action
- [X] Create `CreateRecipeAction` class in `app/Filament/Resources/ProductMixResource/Actions/`
  - [X] Create Actions directory if it doesn't exist
  - [X] Implement action class with proper namespace
  - [X] Add modal configuration
- [X] This action will handle the recipe creation modal form
  - [X] Configure modal form schema
  - [X] Set up form validation
- [X] Will use existing RecipeForm schema but make it context-aware for the selected variety
  - [X] Import RecipeForm components
  - [X] Modify schema for context-aware usage

#### 2. Modify ProductMix Form Recipe Field
- [X] Update `getRecipeField()` in `ProductMixForm.php` to include:
  - [X] Add suffix action button for "Create New Recipe"
  - [X] Configure action button styling and icon
  - [X] Action will trigger modal with recipe creation form
  - [X] After recipe creation, refresh the options and auto-select the new recipe
    - [X] Implement refresh callback
    - [X] Set up auto-selection of new recipe

#### 3. Context-Aware Recipe Creation
- [X] When creating recipe from ProductMix form, pre-populate:
  - [X] `master_seed_catalog_id` from the selected variety
  - [X] `master_cultivar_id` from the selected cultivar
  - [X] Auto-generate recipe name based on variety/cultivar
- [X] Lock these fields in the modal since they're already determined
  - [X] Disable variety selection field
  - [X] Disable cultivar selection field
  - [X] Show read-only context information

#### 4. Modal Implementation
- [X] Use Filament's `CreateAction` with modal
  - [X] Configure modal size and styling
  - [X] Set up modal triggers
- [X] Custom form schema that reuses RecipeForm components
  - [X] Extract reusable form components
  - [X] Configure context-specific schema
- [X] Handle success callback to refresh recipe options
  - [X] Implement success notification
  - [X] Update parent form state
- [X] Show success notification with recipe name
  - [X] Configure notification message
  - [X] Add recipe name to notification

#### 5. Enhanced UX Features
- [X] Show variety/cultivar context in modal title
  - [X] Dynamic modal title generation
  - [X] Include variety and cultivar names
- [X] Disable variety/cultivar fields since they're pre-determined
  - [X] Make fields read-only or hidden
  - [X] Show context information instead
- [X] Focus on recipe-specific parameters (growing days, yield, etc.)
  - [X] Highlight important recipe fields
  - [X] Organize form sections logically
- [X] Validate recipe creation before closing modal
  - [X] Add form validation rules
  - [X] Prevent modal close on validation errors

#### 6. Integration Points
- [X] Ensure new recipes appear immediately in dropdown
  - [X] Refresh recipe options after creation
  - [X] Update dropdown state
- [X] Handle form state updates after recipe creation
  - [X] Maintain form data integrity
  - [X] Update related form fields
- [X] Maintain existing validation for ProductMix percentages
  - [X] Ensure percentage validation still works
  - [X] Preserve existing form behavior
- [X] Preserve other form data during recipe creation process
  - [X] Prevent data loss during modal operations
  - [X] Maintain form state consistency

### Files to Modify/Create

#### New Files:
- [X] `app/Filament/Resources/ProductMixResource/Actions/CreateRecipeAction.php`
  - [X] Create class structure
  - [X] Implement modal configuration
  - [X] Add form schema
  - [X] Handle success/error callbacks

#### Modified Files:
- [X] `app/Filament/Resources/ProductMixResource/Forms/ProductMixForm.php`
  - [X] Update `getRecipeField()` method
    - [X] Add suffixAction configuration
    - [X] Import CreateRecipeAction
    - [X] Configure action trigger
  - [X] Add action trigger and handling
    - [X] Set up callback functions
    - [X] Handle form state updates

### Key Implementation Details:
- [X] Use Filament's `suffixAction()` on Select field
  - [X] Configure suffix action properly
  - [X] Add appropriate styling
- [X] Leverage existing RecipeForm schema with modifications
  - [X] Import existing form components
  - [X] Adapt for modal context
- [X] Implement proper state management for form updates
  - [X] Handle parent form state
  - [X] Manage modal form state
- [X] Add proper error handling and user feedback
  - [X] Configure error messages
  - [X] Add success notifications

### Testing and Validation
- [X] Test recipe creation flow from ProductMix form
  - [X] Test with different varieties/cultivars
  - [X] Verify form state preservation
- [X] Verify new recipes appear in dropdown immediately
  - [X] Test dropdown refresh
  - [X] Verify auto-selection works
- [X] Test error handling and validation
  - [X] Test form validation errors
  - [X] Test network/database errors
- [X] Ensure existing ProductMix functionality still works
  - [X] Test percentage validation
  - [X] Test mix component management

### Additional Implementation Tasks Completed
- [X] Fix ProductMixResource import issue (Tables namespace)
- [X] Fix CreateRecipeAction class to use correct Filament\Forms\Components\Actions\Action type
- [X] Create ProductMixResource/Actions directory structure
- [X] Update RecipeForm field methods to be public for reusability
- [X] Implement context-aware form pre-population with variety/cultivar data
- [X] Add success notifications and form state management
- [X] Fix consumable seeder issues with variety/cultivar matching
- [X] Resolve type mismatch error between page actions and form component actions

### Files Created/Modified Summary
#### New Files Created:
- [X] `app/Filament/Resources/ProductMixResource/Actions/CreateRecipeAction.php` - Modal recipe creation action
- [X] `docs/plans/plan-v002.md` - Implementation plan
- [X] `docs/tasks/tasks-v002.md` - Task tracking file

#### Files Modified:
- [X] `app/Filament/Resources/ProductMixResource.php` - Fixed Tables import
- [X] `app/Filament/Resources/ProductMixResource/Forms/ProductMixForm.php` - Added suffixAction for recipe creation
- [X] `app/Filament/Resources/RecipeResource/Forms/RecipeForm.php` - Made field methods public
- [X] `database/seeders/Data/CurrentSeedConsumableDataSeeder.php` - Fixed variety/cultivar matching

This approach follows Filament best practices, reuses existing code, and provides seamless UX for creating recipes within the ProductMix workflow.

Created: 2025-01-25T18:40:00Z