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
5. **Code changes require DB schema changes** - if a requested code change, absolutely requires a schema change at the DBMS level, then prompt the user to create a new git feature branch
6. **Require evidence of relevance** - when encountering variables, fields, or objects that don't have corresponding DB columns or seem unused, require DIRECT EVIDENCE that they are connected to the current codebase before making accommodations or schema changes. Example: finding a reference to `status` instead of `status_id` doesn't mean we create a `status` column - it means we fix the reference to use `status_id` since we've clearly moved from enums to lookup tables. Don't change the entire codebase architecture because of one dangling legacy reference!

### 🔍 How to Prevent Common Mistakes:

1. **Configuration Sprawl Prevention:**
   - ALWAYS check existing code for configuration patterns
   - Use existing constants and config files
   - Don't hardcode paths - use Laravel helpers like `base_path()`, `storage_path()`
   - When in doubt, grep for similar functionality first

2. **Path Consistency:**
   - Backup files: ONLY use `database/backups/`
   - Temp files: Use `storage/app/temp/`
   - Uploads: Use `storage/app/uploads/`
   - NEVER create new directories without checking CLAUDE.md first

3. **Before Creating ANY New File:**
   - Search for existing files with similar names
   - Check if the functionality already exists
   - Look for TODO comments that might indicate planned features
   - Ask yourself: "Am I creating a duplicate?"

### DURING coding:
1. **Fix, don't replace** - prefer editing existing code over creating new files
2. **Test as you go** - don't write 200 lines before testing
3. **If something fails** - debug and fix it, don't abandon it
4. **Clean up as you go** - remove dead code immediately

### AFTER coding:
1. **Delete any failed attempts** - don't leave broken code behind
2. **Consolidate duplicated functionality** - merge similar files
3. **Update documentation** - keep this file current


### 🗂️ STANDARDIZED BACKUP STORAGE LOCATION
**ALL backup files MUST be stored in: `database/backups/`**

⚠️ **NEVER USE THESE PATHS:**
- `storage/app/backups/` - DO NOT USE
- `storage/app/backups/database/` - DO NOT USE
- Any other location - DO NOT USE

**ALWAYS USE:** `base_path('database/backups/')` in PHP code

**Why this matters:** Multiple backup locations create confusion, duplicate files, and make it impossible to reliably find backups. We've already fixed this issue multiple times - don't reintroduce it!

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
6. **Don't use emojis/emoticons in code or output** - Keep code clean and professional
7. **Don't use `\Log::` - ALWAYS use `use Illuminate\Support\Facades\Log;` and then `Log::`** - This error happens constantly

## ✅ What TO Do

1. **Read before you write** - Understand the existing codebase
2. **Fix before you replace** - Try to repair existing functionality first
3. **Test early and often** - Don't write 100+ lines without testing
4. **Clean up immediately** - Remove any failed attempts right away
5. **Document your changes** - Update this file when you modify the system
6. **Ask Questions** - Whenever you need to prompt the user for clarification

## 🔄 Future Development Guidelines

2. **Extend functionality, don't replace it**
3. **Test with real data and real workflows**
4. **Keep the UI simple and intuitive**
5. **Document any new features in this file**
6. **Prefer code based solutions to altering database schema**
7. **Don't change the names of existing database schema columns**

## 🐳 Docker Development Environment

**IMPORTANT: All Laravel commands must be run inside the Docker container!**

### Required Command Pattern:
```bash
# DON'T DO THIS (will fail - no database connection):
php artisan test

# DO THIS (runs inside container with database access):
docker exec -it php php artisan test
docker exec -it php php artisan optimize:clear
docker exec -it php php artisan migrate
```

### Common Docker Commands:
- **Run tests**: `docker exec -it php php artisan test`
- **Clear cache**: `docker exec -it php php artisan optimize:clear`
- **Run migrations**: `docker exec -it php php artisan migrate`
- **Tinker**: `docker exec -it php php artisan tinker`
- **Check logs**: `docker exec -it php tail -f storage/logs/laravel.log`

### Why This Matters:
- Database connections only work inside the container
- File permissions and paths are different
- Environment variables are container-specific
- **Always use `docker exec` for any artisan commands!**

---

**Remember: The best code is no code. The second best code is code that already exists and works.**