# Claude AI Assistant Guidelines for Catapult Project

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

### DURING coding:
1. **Fix, don't replace** - prefer editing existing code over creating new files
2. **Test as you go** - don't write 200 lines before testing
3. **If something fails** - debug and fix it, don't abandon it
4. **Clean up as you go** - remove dead code immediately

### AFTER coding:
1. **Delete any failed attempts** - don't leave broken code behind
2. **Consolidate duplicated functionality** - merge similar files
3. **Update documentation** - keep this file current

## 📁 Current Database Backup System

### Existing Files (DO NOT DUPLICATE):
- `app/Filament/Pages/DatabaseConsole.php` - Main backup management page ✅ WORKING
- `app/Console/Commands/DatabaseBackupCommand.php` - Backup CLI command
- `app/Console/Commands/DatabaseRestoreCommand.php` - Restore CLI command  
- `app/Console/Commands/SafeBackupCommand.php` - Git-integrated backup
- `app/Services/SimpleBackupService.php` - Core backup logic
- `resources/views/filament/pages/database-console.blade.php` - UI template

### ❌ FAILED/ABANDONED Files (CLEANED UP):
- ~~`app/Models/DatabaseBackup.php`~~ - Virtual model attempt (DELETED)
- ~~`app/Filament/Resources/DatabaseBackupResource.php`~~ - Resource attempt (DELETED)
- ~~`app/Filament/Pages/DatabaseBackups.php`~~ - Duplicate page attempt (DELETED)
- ~~`resources/views/filament/pages/database-backups.blade.php`~~ - Duplicate view (DELETED)

**Status: ✅ CLEANED UP - Only ONE working solution remains**

## 🛠 Key Commands

### Database Operations:
```bash
# Create backup
php artisan db:backup

# List backups  
php artisan db:backup --list

# Restore backup
php artisan db:restore filename.sql --force

# Safe backup with git
php artisan safe:backup --commit-message="Description"
```

### Development:
```bash
# Test commands before implementing
php artisan tinker

# Check routes
php artisan route:list | grep backup

# Clear caches when things break
php artisan optimize:clear
```

## 🧹 Code Cleanup Checklist

Before marking any task complete:

- [ ] Are there any duplicate files doing the same thing?
- [ ] Are there any broken/unused files left behind?
- [ ] Does everything actually work end-to-end?
- [ ] Have you removed any "temporary" test code?
- [ ] Are there any console errors or warnings?
- [ ] Did you test the happy path AND error cases?

## 🚫 What NOT to Do

1. **Don't create multiple solutions** - Pick one approach and make it work
2. **Don't leave broken code** - If you break something, fix it immediately
3. **Don't create "temporary" files** - They become permanent technical debt
4. **Don't ignore errors** - Fix the root cause, don't work around it
5. **Don't create new files if you can extend existing ones**

## ✅ What TO Do

1. **Read before you write** - Understand the existing codebase
2. **Fix before you replace** - Try to repair existing functionality first
3. **Test early and often** - Don't write 100+ lines without testing
4. **Clean up immediately** - Remove any failed attempts right away
5. **Document your changes** - Update this file when you modify the system

## 📋 Current Working Features

### Database Console (`/admin/database-console`):
- ✅ Create backups with custom paths
- ✅ Safe backups with git integration  
- ✅ List all available backups
- ✅ Download backup files
- ✅ Restore from backups
- ✅ Mass delete with multiselect
- ✅ Schema change workflow documentation
- ✅ Full CLI flag support in UI

### Tested Workflows:
- ✅ Basic backup creation and restoration
- ✅ Schema changes with data preservation  
- ✅ Mass backup deletion
- ✅ Git-integrated safe backups

## 🔄 Future Development Guidelines

1. **Always start with the existing DatabaseConsole page**
2. **Extend functionality, don't replace it**
3. **Test with real data and real workflows**
4. **Keep the UI simple and intuitive**
5. **Document any new features in this file**

---

**Remember: The best code is no code. The second best code is code that already exists and works.**