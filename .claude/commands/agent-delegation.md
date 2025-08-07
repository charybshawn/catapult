# Agent Delegation Quick Reference

Use this to understand when Claude should delegate to which agent:

## Available Agents

1. **`filament-resource-builder`** - Filament v3 resources, forms, tables, actions
2. **`laravel-fullstack-architect`** - Laravel architecture, database, migrations, models
3. **`php-laravel-debugger`** - PHP/Laravel debugging, performance, error analysis  
4. **`phpunit-test-creator`** - PHPUnit tests for Laravel/Filament applications
5. **`code-comments-reviewer`** - Code documentation review and standards
6. **`php-documentation-auditor`** - PHP documentation auditing and compliance
7. **`general-purpose`** - Complex research, multi-step tasks, codebase analysis

## Delegation Decision Tree

```
User Request → Analyze Task Type → Choose Agent → Execute → Summarize
```

### Examples:

**"Create a UserResource"** → `filament-resource-builder`
**"Add user-profile relationship"** → `laravel-fullstack-architect`  
**"App is running slow"** → `php-laravel-debugger`
**"Create tests for this feature"** → `phpunit-test-creator`
**"Review code documentation"** → `code-comments-reviewer`
**"Find all instances of X in codebase"** → `general-purpose`

## Director Mode Benefits:

- ✅ **Specialized Expertise**: Each agent has deep domain knowledge
- ✅ **Consistent Quality**: Agents follow specific patterns and standards  
- ✅ **Parallel Processing**: Can launch multiple agents simultaneously
- ✅ **Better Context**: Agents focus on their specialty without distractions
- ✅ **Quality Assurance**: Each agent has built-in quality checks

## Usage Pattern:

1. **Receive user request**
2. **Identify task type(s)**  
3. **Launch appropriate agent(s) using Task tool**
4. **Monitor agent progress**
5. **Summarize results for user**
6. **Follow up if needed**