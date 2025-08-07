# Live Debugging Tools for Agents

## Currently Available (No Installation Needed)

### 1. **Real-time Log Monitoring**
```bash
# Agent can monitor Laravel logs live
tail -f storage/logs/laravel.log

# Filter for specific patterns
grep -i "query\|error\|exception" storage/logs/laravel.log

# Monitor last 100 lines with updates
tail -100f storage/logs/laravel.log | grep -i "order_simulator"
```

### 2. **Tinker Integration for Live Analysis**  
```bash
# Agent can run live database queries
php artisan tinker --execute="
\$hiddenRows = session('order_simulator_hidden_rows', []);
dump(\$hiddenRows);
"

# Test composite ID generation live
php artisan tinker --execute="
\$products = DB::select('SELECT CONCAT(products.id, \"_\", product_price_variations.id) as composite_id, products.name, product_price_variations.name FROM products JOIN product_price_variations ON products.id = product_price_variations.product_id LIMIT 10');
dump(\$products);
"
```

### 3. **Direct Database Investigation**
```bash
# Agent can query database directly during issues
mysql -u sail -p'password' catapult -e "
SELECT 
    CONCAT(p.id, '_', ppv.id) as composite_id,
    p.name as product_name,
    ppv.name as variation_name,
    ppv.price
FROM products p 
JOIN product_price_variations ppv ON p.id = ppv.product_id 
WHERE p.name LIKE '%Broccoli%'
ORDER BY p.name, ppv.name;
"
```

### 4. **Session State Inspection**
```bash
# Check session data during debugging
php artisan tinker --execute="
echo 'Hidden Rows: ';
dump(session('order_simulator_hidden_rows'));
echo 'Quantities: '; 
dump(session('order_simulator_quantities'));
"
```

## Enhanced Debugging Workflow

### For Row Hiding Issues:
1. **Before hiding** - Agent captures current table state
2. **During hiding** - Monitor logs for queries and errors  
3. **After hiding** - Compare session state and database results
4. **Verification** - Check exact SQL queries that were executed

### Example Agent Debug Session:
```bash
# 1. Capture current state
php artisan tinker --execute="dump(session('order_simulator_hidden_rows'))"

# 2. Monitor logs (in separate terminal)
tail -f storage/logs/laravel.log &

# 3. User hides row (agent watches logs)

# 4. Check post-action state
php artisan tinker --execute="dump(session('order_simulator_hidden_rows'))"

# 5. Verify database consistency
mysql -u sail -p'password' catapult -e "SELECT COUNT(*) FROM products JOIN product_price_variations ON products.id = product_price_variations.product_id WHERE products.active = 1"
```

## Agent Integration Benefits

✅ **Real-time visibility** into queries, sessions, and errors
✅ **Live state inspection** during user interactions  
✅ **Database verification** of expected vs actual results
✅ **Performance monitoring** to identify bottlenecks
✅ **Session debugging** to track state changes

## Recommended Next Step

Install Laravel Telescope for even better debugging:
```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

Then agents can access `/telescope` for comprehensive request analysis!