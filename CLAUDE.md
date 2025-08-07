# Claude AI Assistant Guidelines for Catapult Project

Please work through the tasks in tasks.md one at a time and mark each finished task with X.

## 🎯 Project Management Director Mode

Claude should act as a Project Managing Director that delegates tasks to specialized agents. Follow this decision tree:

### 🤖 Agent Delegation Strategy

**ALWAYS use the Task tool with appropriate agents for:**

1. **Filament Resources** → `filament-resource-builder`
   - Creating/modifying Filament resources, forms, tables, or components
   - Converting database schemas to Filament fields 
   - Adding custom actions, bulk actions, and table functionality
   - Example: "Create a ProductResource" or "Add export action to table"

2. **Laravel Architecture** → `laravel-fullstack-architect`  
   - Database design, migrations, relationships
   - Model creation and Eloquent relationships
   - Laravel best practices and conventions
   - Example: "Design user-profile relationship" or "Create migration"

3. **PHP/Laravel Debugging** → `php-laravel-debugger`
   - Performance issues, query optimization, N+1 queries
   - Error analysis and troubleshooting, stack trace interpretation
   - Memory leaks, slow queries, database schema issues
   - Data flow tracing, session debugging, cache problems
   - Example: "App is slow", "Getting SQLSTATE error", "Wrong data being saved"

4. **Testing** → `phpunit-test-creator`
   - Creating PHPUnit tests for Laravel/Filament
   - Feature tests, unit tests
   - Test coverage for new features
   - Example: "Create tests for UserResource" or "Test authentication"

5. **Code Documentation** → `php-documentation-auditor` or `code-comments-reviewer`
   - Reviewing PHP docblocks for proper documentation standards
   - Ensuring PSR-12, Laravel, and Filament commenting standards compliance
   - Automated quality assessment and documentation completeness
   - Example: "Review documentation" or "Add missing docblocks"

6. **General/Complex Tasks** → `general-purpose`
   - Multi-step analysis and research
   - Complex searches across codebase
   - When unsure which agent to use

### 🎪 Director's Role:
- **Analyze the request** and determine the best agent
- **Launch agents proactively** - don't wait for user to ask
- **Coordinate multiple agents** for complex tasks
- **Summarize agent results** for the user
- **Ensure task completion** across all agents 

## 🚨 CRITICAL: Avoid Code Sprawl at All Costs

**Code sprawl** is the #1 enemy of this codebase. It happens when AI assistants:
1. Try one approach and it fails
2. Immediately jump to a completely different approach 
3. Leave the failed code behind
4. Create multiple half-working solutions
5. Result: Spaghetti codebase with dead code everywhere

### ⚠️ WARNING SIGNS OF CODE SPRAWL:
- Creating multiple pages/resources/classes that do the same thing
- Leaving broken/unused files in the codebase
- Jumping between approaches without cleaning up
- Creating "temporary" solutions that become permanent
- Not finishing what you start before moving on

## 🎯 The RIGHT Approach

### BEFORE starting any code:
1. **Read existing code first** - understand what already exists
2. **Look for existing solutions** - can you extend/fix what's there?
3. **Plan your approach** - don't code until you have a clear plan
4. **Commit to ONE approach** - see it through to completion
5. **Code changes require DB schema changes** - if a requested code change, absolutely requires a schema change at the DBMS level, then prompt the user to create a new git feature branch
6. **Require evidence of relevance** - when encountering variables, fields, or objects that don't have corresponding DB columns or seem unused, require DIRECT EVIDENCE that they are connected to the current codebase before making accommodations or schema changes. Example: finding a reference to `status` instead of `status_id` doesn't mean we create a `status` column - it means we fix the reference to use `status_id` since we've clearly moved from enums to lookup tables. Don't change the entire codebase architecture because of one dangling legacy reference!


## 🏗️ Filament Resource Architecture

**MANDATORY READING:** `docs/FILAMENT_RESOURCE_ARCHITECTURE_GUIDE.md`

### 🎯 Core Rule: Work WITH Filament, Not Against It

When working with Filament resources, forms, tables, or pages:

1. **NEVER create custom observers, services, or blade templates** - Use Filament's built-in patterns
2. **ALWAYS extend Filament classes** - Don't create parallel systems
3. **ORGANIZE Filament components** - Don't reinvent them
4. **DELEGATE to specialized classes** - Keep main resource files under 150 lines

### 📁 Required File Structure for Resources:
```
app/Filament/Resources/
├── XxxResource.php                    # Main coordinator (MAX 150 lines)
├── XxxResource/
│   ├── Forms/XxxForm.php             # Returns Filament form schema
│   ├── Tables/XxxTable.php           # Returns Filament table components
│   ├── Actions/XxxAction.php         # Custom actions with minimal logic
│   └── Pages/                        # Extend Filament page classes
```

### 🎨 HTML and Display Separation:
**ALWAYS use Blade views for complex HTML output:**

1. **Actions should delegate to views** - Don't build HTML strings in action classes
2. **Create dedicated view files** - Store in `resources/views/filament/actions/` or similar
3. **Pass data to views** - Let Blade handle the presentation logic
4. **Keep actions lightweight** - Actions should orchestrate, not generate HTML

**Example Pattern:**
```php
// ✅ GOOD: Action delegates to view
return Action::make('debug')
    ->action(function ($record) {
        $html = view('filament.actions.crop-batch-debug', [
            'record' => $record,
            'firstCrop' => Crop::where('crop_batch_id', $record->id)->first(),
        ])->render();
        
        Notification::make()->body($html)->send();
    });

// ❌ BAD: HTML built in action
return Action::make('debug')
    ->action(function ($record) {
        $html = '<div class="mb-4">';
        $html .= '<h3>' . $title . '</h3>';
        // ... more HTML building
    });
```