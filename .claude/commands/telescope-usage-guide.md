# Laravel Telescope - Live Debugging Guide

## ✅ Installation Complete!

Laravel Telescope has been successfully installed and configured for your Catapult project.

## 🔍 Accessing Telescope

### Web Interface
- **URL**: http://catapult.test/telescope
- **Authentication**: Uses your existing Laravel auth (same as Filament admin)

### What You'll See:
- **Requests** - All HTTP requests with timing and query data
- **Queries** - Database queries with execution time and parameters
- **Models** - Eloquent model events (created, updated, deleted)
- **Jobs** - Queue job execution and failures
- **Exceptions** - All application errors with stack traces
- **Notifications** - Filament notifications and emails
- **Cache** - Cache operations (get, set, forget)

## 🚀 Agent Integration Benefits

### For Row Hiding Debug:
1. **Navigate to**: http://catapult.test/telescope
2. **Hide a row** in order simulator 
3. **Watch in real-time**:
   - Exact SQL queries executed
   - Livewire component updates
   - Session state changes
   - Any errors or exceptions

### Available Telescope Data:
```bash
# Agents can now reference Telescope data by:
# 1. Direct web interface inspection
# 2. Database queries to telescope_entries table
# 3. Real-time monitoring during user actions

# Example: Query recent requests
mysql -u root -p'' catapult -e "
SELECT type, content->>'$.method' as method, content->>'$.uri' as uri, 
       created_at 
FROM telescope_entries 
WHERE type = 'request' 
ORDER BY created_at DESC 
LIMIT 10;
"
```

## 📊 Debugging Workflow

### Before Issue Reproduction:
1. **Clear Telescope data**: Click "Clear Entries" in Telescope UI
2. **Start monitoring**: Keep `/telescope` open in browser tab

### During Issue Reproduction:
1. **Perform the action** (hide row, etc.)
2. **Watch requests** in Telescope for:
   - Livewire update requests
   - Database query execution
   - Any error responses

### After Issue Analysis:
1. **Check Queries tab** - See exact SQL with parameters
2. **Check Requests tab** - Review request/response cycle  
3. **Check Exceptions tab** - Any errors thrown
4. **Check Models tab** - What data was actually changed

## 🎯 Specific Use Cases

### Row Hiding Investigation:
- **Queries tab** will show the exact `whereNotIn` filtering SQL
- **Requests tab** will show Livewire component updates
- **Models tab** will show any session data changes
- **Exceptions tab** will catch any hidden errors

### Performance Analysis:
- **Request timing** - How long each action takes
- **Query count** - Identify N+1 query issues  
- **Memory usage** - Track resource consumption
- **Database load** - See which queries are slowest

## 🔧 Advanced Agent Debugging

### Query Telescope Data Directly:
```bash
# Recent database queries
mysql -u root -p'' catapult -e "
SELECT content->>'$.sql' as query, 
       content->>'$.time' as time_ms,
       created_at 
FROM telescope_entries 
WHERE type = 'query' 
ORDER BY created_at DESC 
LIMIT 5;
"

# Recent exceptions
mysql -u root -p'' catapult -e "
SELECT content->>'$.class' as exception_class,
       content->>'$.message' as message,
       created_at
FROM telescope_entries 
WHERE type = 'exception'
ORDER BY created_at DESC 
LIMIT 3;
"
```

## 🎨 Configuration

Telescope is configured to run only in local development:
- **Production**: Automatically disabled  
- **Local**: Full monitoring enabled
- **Storage**: Database (easily queryable by agents)
- **Retention**: 24 hours (configurable in config/telescope.php)

## 🚀 Next Steps

1. **Test the installation**: Visit http://catapult.test/telescope
2. **Try the row hiding bug**: Monitor in real-time via Telescope
3. **Share findings**: Agents can now reference specific Telescope data
4. **Performance monitoring**: Use for ongoing optimization

**Telescope is now ready for live debugging!** 🎯