# Catapult Agricultural Management System - Documentation Policy

## 📋 PHP Documentation Standards

Every PHP file in the Catapult agricultural management system **MUST** be comprehensively documented according to these standards.

### 🎯 Core Requirements

#### 1. PSR-12 Compliance
- **Complete class-level PHPDoc blocks** with comprehensive agricultural business context
- **Complete method-level PHPDoc blocks** with agricultural workflow explanations
- **All @param annotations** with agricultural parameter context
- **All @return annotations** with agricultural return value explanations
- **Proper @throws documentation** for agricultural constraint violations

#### 2. Agricultural Business Context
- **Microgreens Production Focus**: All documentation must explain agricultural concepts
- **Business Workflow Integration**: Explain how code fits into farm operations
- **Agricultural Terminology**: Use proper microgreens cultivation terminology
- **Production Timeline Context**: Explain timing constraints and deadlines
- **Customer Impact Documentation**: Connect technical features to business outcomes

#### 3. Comprehensive Coverage Requirements
- **New Classes**: Must have complete agricultural business context documentation
- **New Methods**: Must explain agricultural workflow purpose and integration
- **Modified Code**: All changes must update documentation to reflect agricultural context
- **Business Rules**: Document agricultural constraints and validation logic
- **Integration Points**: Explain connections to other agricultural systems

### 🔧 Automated Documentation Validation

#### Hook System
The project includes automated hooks that trigger when PHP files are modified:

- **Write Tool**: Triggers documentation validation for new files
- **Edit Tool**: Triggers documentation review for modified files  
- **MultiEdit Tool**: Triggers comprehensive documentation audit for batch changes

#### Validation Process
1. **Automatic Detection**: Hooks detect PHP file modifications
2. **Validation Script**: Runs comprehensive documentation checks
3. **Agent Recommendation**: Suggests launching enhanced-documentation-auditor agent
4. **Standards Verification**: Ensures compliance with Catapult documentation standards

### 🤖 Enhanced Documentation Auditor Agent

#### When to Use
Launch the `enhanced-documentation-auditor` agent:
- **After creating new PHP files**
- **After modifying existing PHP files**
- **When documentation hooks indicate validation needed**
- **Before code reviews or pull requests**
- **When onboarding new developers**

#### Agent Capabilities
- **Comprehensive Documentation Review**: Analyzes entire files for agricultural context
- **PSR-12 Compliance Verification**: Ensures professional documentation standards
- **Agricultural Context Enhancement**: Adds microgreens production workflow context
- **Integration Documentation**: Explains how code connects to agricultural operations
- **Quality Assurance**: Validates documentation meets Catapult standards

### 📈 Documentation Quality Standards

#### Agricultural Business Context Tags
Use specialized documentation tags:
```php
@agricultural_workflow Microgreens production scheduling and timeline management
@business_domain Agricultural production planning and operational efficiency
@production_focus Cultivation workflow optimization for continuous harvesting
@customer_impact Direct connection to customer delivery commitments
```

#### Comprehensive Examples
Include practical agricultural scenarios:
```php
/**
 * Example: Calculating planting schedules for customer orders
 * 
 * For a Tuesday delivery order, this calculates:
 * - Harvest date: Monday (day before delivery)
 * - Plant date: Thursday (7 days before harvest for microgreens)
 * - Seed soaking: Tuesday (2 days before planting)
 */
```

#### Performance and Business Justification
Document optimization with agricultural context:
```php
/**
 * Performance: Cached to prevent N+1 queries during peak planting season
 * Business Impact: Ensures rapid response during daily production planning
 * Agricultural Context: Critical for managing 50+ varieties across multiple growing cycles
 */
```

### ✅ Compliance Verification

#### Before Code Commits
1. **Run Documentation Validation**: Use hooks or manual agent launch
2. **Verify Agricultural Context**: Ensure comprehensive microgreens production context
3. **Check Integration Documentation**: Confirm workflow explanations are complete
4. **Validate Business Impact**: Ensure customer and operational impact is documented

#### Code Review Requirements
1. **Documentation Coverage**: All new/modified code must have agricultural context
2. **Business Logic Clarity**: Agricultural concepts must be clearly explained
3. **Integration Context**: How code fits into farm operations must be documented
4. **Quality Standards**: Must meet established Catapult documentation standards

### 🎯 Success Metrics

#### Documentation Quality Indicators
- **Agricultural Terminology Usage**: Proper microgreens production terminology
- **Business Context Coverage**: Complete explanation of agricultural workflows
- **Integration Documentation**: Clear connections to farm operations
- **Developer Onboarding**: New developers can understand agricultural concepts from documentation

#### Maintenance Benefits
- **Reduced Debugging Time**: Clear agricultural context accelerates troubleshooting
- **Improved Code Reviews**: Comprehensive documentation enables effective reviews
- **Enhanced System Evolution**: Well-documented agricultural concepts support feature development
- **Business Continuity**: Agricultural knowledge preserved in codebase documentation

---

**Remember**: Documentation is not just for technical understanding - it preserves the agricultural domain knowledge that makes Catapult effective for microgreens production management.