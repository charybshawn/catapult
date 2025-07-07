# Dead Code Analysis Report

## Executive Summary

This report identifies dead/unused code, legacy patterns, and broken features in the Laravel codebase. Key findings include improper use of Laravel facades, debug routes that should be removed, and potential performance issues with logging in model relationships.

## 1. Dead/Unused Code

### 1.1 Debug Routes in Production Code
**File:** `routes/web.php`
- **Lines 11-18:** Debug login POST route
- **Lines 20-32:** Debug CSRF route
- **Lines 76-81:** Temporary Filament admin login POST route fix

**Recommendation:** Remove these debug routes before deploying to production.

### 1.2 TODO Comments
**File:** `app/Filament/Resources/CropPlanResource.php`
- TODO: Implement crop generation logic

**File:** `app/Filament/Resources/CustomerResource.php`
- TODO: Send email if requested

### 1.3 Commented Out Code
**File:** `app/Models/User.php`
- **Line 5:** Commented out `use Illuminate\Contracts\Auth\MustVerifyEmail;`

## 2. Legacy/Outdated Patterns

### 2.1 Improper Log Facade Usage
Multiple files use `\Log::` instead of importing the facade properly:

**File:** `app/Models/Product.php`
- **Line 1133:** Uses `\Log::info()` instead of `Log::info()`

**File:** `app/Models/Consumable.php`
- **Line 97:** Uses full namespace `\Illuminate\Support\Facades\Log::`

**File:** `app/Models/Crop.php`
- **Lines 342, 352, 363, 376, 417, 432:** Uses full namespace

**File:** `app/Models/SeedEntry.php`
- **Line 55:** Uses full namespace

**Recommendation:** Import `use Illuminate\Support\Facades\Log;` at the top and use `Log::` throughout.

### 2.2 Validator::make Pattern
**File:** `app/Models/Product.php`
- **Line 117:** Uses `Validator::make` in model boot method

**Recommendation:** Consider using Form Request validation or custom validation rules instead of inline validation in models.

### 2.3 Deprecated Price Attributes
**File:** `app/Models/Product.php`
- **Lines 759-816:** Deprecated getter methods for price attributes (marked with @deprecated)
  - `getBasePriceAttribute()`
  - `getWholesalePriceAttribute()`
  - `getBulkPriceAttribute()`
  - `getSpecialPriceAttribute()`

**Recommendation:** These are marked as deprecated but still being used. Plan migration to price variations system.

## 3. Non-functioning/Broken Features

### 3.1 ProductMix Relationship with Debug Logging
**File:** `app/Models/Product.php`
- **Lines 562-580:** The `productMix()` relationship has try-catch with logging that runs on every access

**Issue:** This creates performance overhead and log spam. The logging happens every time the relationship is accessed.

**Recommendation:** Remove the debug logging or move it to a dedicated debug mode.

### 3.2 Potential N+1 Query Issues
**File:** `app/Models/Product.php`
- Multiple methods check `$this->relationLoaded()` but don't consistently use it
- `getVarietiesAttribute()` method loads relationships inside accessor

## 4. Unused Services/Classes

### 4.1 DebugService
**File:** `app/Services/DebugService.php`
- Contains debug methods that write JSON files to storage
- `checkpoint()` method creates timestamped debug files

**Recommendation:** Verify if this is still needed; consider removing in production.

### 4.2 Profile Controller Routes
**File:** `routes/web.php`
- Lines 39-41: Profile routes defined but using Filament for user management

**Recommendation:** Verify if these routes are needed alongside Filament admin.

## 5. Code Quality Issues

### 5.1 Long Methods
**File:** `app/Models/Product.php`
- `booted()` method (lines 99-218) is extremely long with multiple responsibilities
- Should be refactored into smaller methods

### 5.2 Magic Numbers
**File:** `app/Models/Product.php`
- Line 929: Hard-coded 365 days for inventory expiration calculation
- Line 966-979: Hard-coded inventory check messages

## 6. Recommendations

1. **Immediate Actions:**
   - Remove debug routes from `routes/web.php`
   - Fix all `\Log::` usage to use proper facade import
   - Remove or properly gate the DebugService usage

2. **Short-term Actions:**
   - Complete TODO items or remove them
   - Refactor long methods in Product model
   - Remove deprecated price attribute getters after migration

3. **Long-term Actions:**
   - Implement proper error handling strategy instead of inline try-catch with logging
   - Consider using Laravel's built-in debugging tools instead of custom DebugService
   - Audit all relationships for N+1 query issues

## Files Requiring Attention

1. `/Users/shawn/Documents/GitHub/catapult/app/Models/Product.php` - Multiple issues
2. `/Users/shawn/Documents/GitHub/catapult/routes/web.php` - Debug routes
3. `/Users/shawn/Documents/GitHub/catapult/app/Models/Consumable.php` - Log usage
4. `/Users/shawn/Documents/GitHub/catapult/app/Models/Crop.php` - Log usage
5. `/Users/shawn/Documents/GitHub/catapult/app/Models/SeedEntry.php` - Log usage
6. `/Users/shawn/Documents/GitHub/catapult/app/Services/DebugService.php` - Review necessity