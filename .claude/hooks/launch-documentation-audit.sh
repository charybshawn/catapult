#!/bin/bash

# Claude Code Documentation Auditor Launcher
# Helper script to launch enhanced-documentation-auditor agent for specific files

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

echo -e "${PURPLE}🤖 Enhanced Documentation Auditor Agent Launcher${NC}"
echo ""

if [ -z "$1" ]; then
    echo -e "${RED}❌ Usage: $0 <php-file-path>${NC}"
    echo "Example: $0 app/Services/CropPlanCalculatorService.php"
    exit 1
fi

FILE_PATH="$1"

# Check if file exists
if [ ! -f "$FILE_PATH" ]; then
    echo -e "${RED}❌ File not found: $FILE_PATH${NC}"
    exit 1
fi

# Check if it's a PHP file
if [[ "$FILE_PATH" != *.php ]]; then
    echo -e "${YELLOW}⚠️  Warning: $FILE_PATH is not a PHP file${NC}"
    echo "Documentation auditor is optimized for PHP files"
fi

echo -e "${BLUE}📁 Target File: $FILE_PATH${NC}"
echo -e "${GREEN}🎯 Mission: Comprehensive Agricultural Documentation Enhancement${NC}"
echo ""

echo -e "${YELLOW}📋 DOCUMENTATION STANDARDS TO APPLY:${NC}"
echo "• PSR-12 Compliance with comprehensive agricultural business context"
echo "• Microgreens production workflow explanations"
echo "• Agricultural terminology and domain knowledge integration"
echo "• Business impact and operational context documentation"  
echo "• Complete parameter and return value documentation"
echo "• Integration context with farm management workflows"
echo ""

echo -e "${GREEN}✅ AGENT PROMPT READY:${NC}"
echo "────────────────────────────────────────────────────"
echo -e "${BLUE}Please review and enhance the documentation for:${NC} $FILE_PATH"
echo ""
echo -e "${BLUE}COMPREHENSIVE VALIDATION REQUIREMENTS:${NC}"
echo "1. **PSR-12 Compliance**: Verify complete class and method PHPDoc blocks with agricultural context"
echo "2. **Agricultural Business Context**: Ensure comprehensive microgreens production workflow documentation"
echo "3. **Change Documentation**: Verify new/modified code has proper agricultural business explanations"
echo "4. **Integration Context**: Check documentation explains how code fits into agricultural workflows"
echo "5. **Parameter Documentation**: Ensure all @param and @return annotations include agricultural context"
echo "6. **Business Impact**: Document operational benefits and customer impact"
echo ""
echo -e "${BLUE}CATAPULT AGRICULTURAL STANDARDS:${NC}"
echo "• All PHP files must have comprehensive agricultural business context"
echo "• New methods require full agricultural workflow explanations"  
echo "• Modified methods need updated documentation reflecting agricultural purpose"
echo "• Classes require detailed agricultural domain context with microgreens focus"
echo "• Documentation should help new developers understand agricultural concepts"
echo ""
echo -e "${BLUE}TASKS FOR ENHANCED-DOCUMENTATION-AUDITOR:${NC}"
echo "1. Read and analyze the target file comprehensively"
echo "2. Identify any documentation gaps or areas needing agricultural context enhancement"
echo "3. Add/update documentation with comprehensive agricultural business context"
echo "4. Ensure all changes are properly documented with business justification"
echo "5. Verify integration with broader microgreens production workflows"
echo "6. Report what documentation enhancements were made"
echo ""
echo -e "${GREEN}🎯 FOCUS: Ensure the file meets the comprehensive agricultural documentation standards established throughout the Catapult microgreens management project.${NC}"
echo "────────────────────────────────────────────────────"
echo ""
echo -e "${PURPLE}Ready to launch enhanced-documentation-auditor agent with this prompt!${NC}"