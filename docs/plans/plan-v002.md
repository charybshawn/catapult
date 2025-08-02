# Plan v002

## Plan: Add Dynamic Recipe Creation to ProductMix Forms

### Current State Analysis
- ProductMix form has recipe selection dropdown that shows existing active recipes
- Recipe creation is currently separate in RecipeResource
- The recipe field is optional with a placeholder "Use default recipe"
- ProductMix components store `recipe_id` in pivot table

### Implementation Plan

#### 1. **Create Recipe Creation Action**
- Create `CreateRecipeAction` class in `app/Filament/Resources/ProductMixResource/Actions/`
- This action will handle the recipe creation modal form
- Will use existing RecipeForm schema but make it context-aware for the selected variety

#### 2. **Modify ProductMix Form Recipe Field**
- Update `getRecipeField()` in `ProductMixForm.php` to include:
  - Suffix action button for "Create New Recipe"
  - Action will trigger modal with recipe creation form
  - After recipe creation, refresh the options and auto-select the new recipe

#### 3. **Context-Aware Recipe Creation**
- When creating recipe from ProductMix form, pre-populate:
  - `master_seed_catalog_id` from the selected variety
  - `master_cultivar_id` from the selected cultivar
  - Auto-generate recipe name based on variety/cultivar
- Lock these fields in the modal since they're already determined

#### 4. **Modal Implementation**
- Use Filament's `CreateAction` with modal
- Custom form schema that reuses RecipeForm components
- Handle success callback to refresh recipe options
- Show success notification with recipe name

#### 5. **Enhanced UX Features**
- Show variety/cultivar context in modal title
- Disable variety/cultivar fields since they're pre-determined
- Focus on recipe-specific parameters (growing days, yield, etc.)
- Validate recipe creation before closing modal

#### 6. **Integration Points**
- Ensure new recipes appear immediately in dropdown
- Handle form state updates after recipe creation
- Maintain existing validation for ProductMix percentages
- Preserve other form data during recipe creation process

### Files to Modify/Create

#### New Files:
1. `app/Filament/Resources/ProductMixResource/Actions/CreateRecipeAction.php`

#### Modified Files:
1. `app/Filament/Resources/ProductMixResource/Forms/ProductMixForm.php`
   - Update `getRecipeField()` method
   - Add action trigger and handling

### Key Implementation Details:
- Use Filament's `suffixAction()` on Select field
- Leverage existing RecipeForm schema with modifications
- Implement proper state management for form updates
- Add proper error handling and user feedback

This approach follows Filament best practices, reuses existing code, and provides seamless UX for creating recipes within the ProductMix workflow.

Created: 2025-01-25T18:39:00Z