# Database Performance Optimization TODO

## 🚨 Critical Performance Issues Identified

**Status**: The database schema is **NOT production-ready** and will have severe performance issues under load.

**Key Problem**: Complete absence of database indexes and foreign key constraints causing full table scans on all queries.

---

## Phase 1: Critical Index Creation (HIGH PRIORITY)

### 1.1 Foreign Key Constraints with Indexes

**Why Critical**: Foreign keys provide both referential integrity and automatic index creation for join optimization.

#### Core Business Tables
```sql
-- Orders table (most critical for business operations)
ALTER TABLE orders ADD CONSTRAINT fk_orders_user_id FOREIGN KEY (user_id) REFERENCES users(id);
ALTER TABLE orders ADD CONSTRAINT fk_orders_customer_id FOREIGN KEY (customer_id) REFERENCES customers(id);
ALTER TABLE orders ADD CONSTRAINT fk_orders_status_id FOREIGN KEY (status_id) REFERENCES order_statuses(id);
ALTER TABLE orders ADD CONSTRAINT fk_orders_order_type_id FOREIGN KEY (order_type_id) REFERENCES order_types(id);
ALTER TABLE orders ADD CONSTRAINT fk_orders_crop_status_id FOREIGN KEY (crop_status_id) REFERENCES crop_statuses(id);
ALTER TABLE orders ADD CONSTRAINT fk_orders_fulfillment_status_id FOREIGN KEY (fulfillment_status_id) REFERENCES fulfillment_statuses(id);
ALTER TABLE orders ADD CONSTRAINT fk_orders_payment_status_id FOREIGN KEY (payment_status_id) REFERENCES payment_statuses(id);
ALTER TABLE orders ADD CONSTRAINT fk_orders_delivery_status_id FOREIGN KEY (delivery_status_id) REFERENCES delivery_statuses(id);
ALTER TABLE orders ADD CONSTRAINT fk_orders_parent_recurring_order_id FOREIGN KEY (parent_recurring_order_id) REFERENCES orders(id);

-- Order Items (critical for order management)
ALTER TABLE order_products ADD CONSTRAINT fk_order_products_order_id FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE;
ALTER TABLE order_products ADD CONSTRAINT fk_order_products_product_id FOREIGN KEY (product_id) REFERENCES products(id);
ALTER TABLE order_products ADD CONSTRAINT fk_order_products_price_variation_id FOREIGN KEY (price_variation_id) REFERENCES product_price_variations(id);

-- Products (core inventory)
ALTER TABLE products ADD CONSTRAINT fk_products_category_id FOREIGN KEY (category_id) REFERENCES categories(id);
ALTER TABLE products ADD CONSTRAINT fk_products_master_seed_catalog_id FOREIGN KEY (master_seed_catalog_id) REFERENCES master_seed_catalog(id);
ALTER TABLE products ADD CONSTRAINT fk_products_product_mix_id FOREIGN KEY (product_mix_id) REFERENCES product_mixes(id);

-- Crops (production tracking)
ALTER TABLE crops ADD CONSTRAINT fk_crops_recipe_id FOREIGN KEY (recipe_id) REFERENCES recipes(id);
ALTER TABLE crops ADD CONSTRAINT fk_crops_order_id FOREIGN KEY (order_id) REFERENCES orders(id);
ALTER TABLE crops ADD CONSTRAINT fk_crops_current_stage_id FOREIGN KEY (current_stage_id) REFERENCES crop_stages(id);
```

#### Supporting Tables
```sql
-- Users and Authentication
ALTER TABLE users ADD CONSTRAINT fk_users_customer_type_id FOREIGN KEY (customer_type_id) REFERENCES customer_types(id);

-- Customers
ALTER TABLE customers ADD CONSTRAINT fk_customers_customer_type_id FOREIGN KEY (customer_type_id) REFERENCES customer_types(id);

-- Inventory Management
ALTER TABLE inventory_transactions ADD CONSTRAINT fk_inventory_transactions_product_id FOREIGN KEY (product_id) REFERENCES products(id);
ALTER TABLE inventory_transactions ADD CONSTRAINT fk_inventory_transactions_transaction_type_id FOREIGN KEY (transaction_type_id) REFERENCES inventory_transaction_types(id);
ALTER TABLE inventory_transactions ADD CONSTRAINT fk_inventory_transactions_crop_id FOREIGN KEY (crop_id) REFERENCES crops(id);

-- Product Management
ALTER TABLE product_price_variations ADD CONSTRAINT fk_product_price_variations_product_id FOREIGN KEY (product_id) REFERENCES products(id);
ALTER TABLE product_inventories ADD CONSTRAINT fk_product_inventories_product_id FOREIGN KEY (product_id) REFERENCES products(id);

-- Recipes and Production
ALTER TABLE recipe_watering_schedule ADD CONSTRAINT fk_recipe_watering_schedule_recipe_id FOREIGN KEY (recipe_id) REFERENCES recipes(id);
```

**Expected Impact**: 10-50x improvement in join query performance.

### 1.2 Performance-Critical Column Indexes

**Why Critical**: These columns are frequently used in WHERE, ORDER BY, and filtering operations.

#### Activity Log (Will become major bottleneck)
```sql
-- Time-based queries (most common)
CREATE INDEX idx_activity_log_created_at ON activity_log(created_at);
CREATE INDEX idx_activity_log_updated_at ON activity_log(updated_at);

-- Entity lookups (very frequent)
CREATE INDEX idx_activity_log_subject ON activity_log(subject_type, subject_id);
CREATE INDEX idx_activity_log_causer ON activity_log(causer_type, causer_id);

-- Activity filtering
CREATE INDEX idx_activity_log_event ON activity_log(log_name, event);

-- Performance monitoring
CREATE INDEX idx_activity_log_execution_time ON activity_log(execution_time_ms);

-- Compound index for common query pattern
CREATE INDEX idx_activity_log_subject_time ON activity_log(subject_type, subject_id, created_at);
```

#### Orders (Business Critical)
```sql
-- Date-based scheduling queries
CREATE INDEX idx_orders_harvest_date ON orders(harvest_date);
CREATE INDEX idx_orders_delivery_date ON orders(delivery_date);

-- Status filtering (very frequent)
CREATE INDEX idx_orders_customer_type ON orders(customer_type);

-- Recurring order processing
CREATE INDEX idx_orders_recurring ON orders(is_recurring, is_recurring_active);
CREATE INDEX idx_orders_recurring_dates ON orders(recurring_start_date, recurring_end_date);
CREATE INDEX idx_orders_next_generation ON orders(next_generation_date);

-- Billing and invoicing
CREATE INDEX idx_orders_billing_frequency ON orders(billing_frequency);
CREATE INDEX idx_orders_billing_period ON orders(billing_period_start, billing_period_end);
```

#### Products (Inventory Management)
```sql
-- Storefront queries
CREATE INDEX idx_products_visibility ON products(active, is_visible_in_store);

-- Inventory management
CREATE INDEX idx_products_stock ON products(total_stock, reorder_threshold);

-- Product lookups
CREATE INDEX idx_products_sku ON products(sku);
CREATE UNIQUE INDEX idx_products_sku_unique ON products(sku) WHERE sku IS NOT NULL;

-- Category filtering
CREATE INDEX idx_products_category_active ON products(category_id, active);
```

#### Crops (Production Tracking)
```sql
-- Workflow management
CREATE INDEX idx_crops_current_stage ON crops(current_stage_id);
CREATE INDEX idx_crops_stage_updated ON crops(stage_updated_at);

-- Harvest planning
CREATE INDEX idx_crops_expected_harvest ON crops(expected_harvest_at);
CREATE INDEX idx_crops_harvest_date ON crops(harvest_date);

-- Tracking operations
CREATE INDEX idx_crops_tray_number ON crops(tray_number);

-- Production monitoring
CREATE INDEX idx_crops_order_stage ON crops(order_id, current_stage_id);
```

**Expected Impact**: 5-20x improvement in filtered query performance.

---

## Phase 2: Database Configuration Optimization

### 2.1 Query Monitoring and Logging

**Current Issue**: Slow query threshold set to 1000ms (too high for early detection).

```php
// config/database.php
'mysql' => [
    // ... existing config
    'options' => [
        PDO::ATTR_TIMEOUT => 30,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
    ],
    'slow_query_log' => env('DB_SLOW_QUERY_LOG', true),
    'slow_query_threshold' => env('DB_SLOW_QUERY_THRESHOLD', 100), // Lower to 100ms
],
```

### 2.2 Activity Log Optimization

**Current Issue**: Extensive logging without performance optimization.

```php
// config/activitylog.php adjustments
'default_log_name' => 'default',
'default_auth_driver' => null,
'subject_returns_soft_deleted_models' => false,
'activity_model' => \App\Models\Activity::class,

// Add performance settings
'batch_size' => 1000,
'async_logging' => true,
'retention_days' => 90, // Prevent unlimited growth
```

### 2.3 Database Connection Optimization

```php
// .env additions
DB_SLOW_QUERY_LOG=true
DB_SLOW_QUERY_THRESHOLD=100
DB_CONNECTION_POOL_SIZE=10
DB_QUERY_CACHE=true
```

**Expected Impact**: 30-50% reduction in database load.

---

## Phase 3: Compound Indexes for Complex Queries

### 3.1 Multi-Column Indexes for Common Query Patterns

```sql
-- Order management compound indexes
CREATE INDEX idx_orders_customer_delivery ON orders(customer_id, delivery_date);
CREATE INDEX idx_orders_status_dates ON orders(status_id, harvest_date, delivery_date);
CREATE INDEX idx_orders_type_frequency ON orders(order_type_id, billing_frequency);

-- Activity log compound indexes
CREATE INDEX idx_activity_log_comprehensive ON activity_log(subject_type, subject_id, created_at, event);
CREATE INDEX idx_activity_log_performance ON activity_log(execution_time_ms, created_at) WHERE execution_time_ms > 100;

-- Product management compound indexes
CREATE INDEX idx_products_category_stock ON products(category_id, active, total_stock);
CREATE INDEX idx_products_visibility_stock ON products(is_visible_in_store, active, total_stock);

-- Crop production compound indexes
CREATE INDEX idx_crops_order_harvest ON crops(order_id, expected_harvest_at, current_stage_id);
CREATE INDEX idx_crops_stage_timing ON crops(current_stage_id, stage_updated_at);

-- Inventory tracking compound indexes
CREATE INDEX idx_inventory_transactions_product_date ON inventory_transactions(product_id, created_at, transaction_type_id);
```

**Expected Impact**: 2-10x improvement for complex queries.

---

## Phase 4: Migration Implementation Strategy

### 4.1 Create Index Migration File

```php
// database/migrations/YYYY_MM_DD_HHMMSS_add_database_performance_indexes.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Disable foreign key checks temporarily for faster execution
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // 1. Add Foreign Key Constraints
        $this->addForeignKeyConstraints();
        
        // 2. Add Performance Indexes
        $this->addPerformanceIndexes();
        
        // 3. Add Compound Indexes
        $this->addCompoundIndexes();
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
    
    // Implementation methods here...
};
```

### 4.2 Testing Strategy

1. **Development Testing**: Run on development database with realistic data volume
2. **Performance Measurement**: Compare query times before/after
3. **Staging Verification**: Test with production-like data volume
4. **Monitoring Setup**: Implement query performance tracking

---

## Performance Impact Estimates

### Before Optimization
- **Order queries**: 500ms - 5s (full table scans)
- **Activity log queries**: 1s - 10s+ (will grow exponentially)
- **Product searches**: 200ms - 2s
- **Crop status queries**: 300ms - 3s
- **Inventory lookups**: 400ms - 4s

### After Optimization
- **Order queries**: 5ms - 50ms (90-99% improvement)
- **Activity log queries**: 10ms - 100ms (95-99% improvement)
- **Product searches**: 2ms - 20ms (90-95% improvement)
- **Crop status queries**: 3ms - 30ms (90-95% improvement)
- **Inventory lookups**: 4ms - 40ms (90-95% improvement)

### Resource Usage Impact
- **Database CPU**: 60-80% reduction
- **Memory usage**: 40-60% reduction
- **Disk I/O**: 70-90% reduction
- **Response times**: 2-5x faster page loads

---

## Risk Assessment

### Low Risk Items ✅
- Adding indexes (doesn't break existing functionality)
- Foreign key constraints (improves data integrity)
- Query logging configuration

### Medium Risk Items ⚠️
- Activity log configuration changes (test thoroughly)
- Database connection pool changes

### High Impact Items 🎯
- Orders table optimization (immediate business benefit)
- Activity log optimization (prevents future bottleneck)
- Product/inventory optimization (user experience improvement)

---

## Implementation Timeline

### Phase 1 (Critical - 2 hours)
1. Create foreign key constraints migration (45 min)
2. Create performance indexes migration (45 min)
3. Test on development environment (30 min)

### Phase 2 (Important - 1 hour)
1. Update database configuration (15 min)
2. Configure query monitoring (15 min)
3. Test configuration changes (30 min)

### Phase 3 (Enhancement - 1 hour)
1. Add compound indexes (30 min)
2. Performance testing and validation (30 min)

**Total Estimated Time**: 4 hours for complete optimization

---

## Success Metrics

### Performance Targets
- Query response times under 100ms for 95% of requests
- Page load times under 2 seconds
- Database CPU usage under 30% during normal operations
- Zero timeout errors under normal load

### Monitoring Setup
- Enable slow query logging (>100ms)
- Set up database performance dashboards
- Configure alerts for degraded performance
- Regular index usage analysis

---

## Notes

- **Critical Priority**: Activity log and orders tables (highest impact)
- **Business Impact**: Immediate improvement in user experience
- **Maintenance**: Regular index analysis and optimization
- **Future**: Consider partitioning for very large tables

This optimization work is **essential** before any production deployment. The current state will not scale beyond a few concurrent users.