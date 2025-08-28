#!/bin/bash

# Claude Code PHP Documentation Validation Hook
# Automatically triggered when PHP files are created, edited, or modified
# Ensures comprehensive agricultural business context documentation

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔍 PHP Documentation Validation Hook${NC}"
echo "File: $1"
echo "Action: $2"
echo "Timestamp: $(date)"

# Check if the file is a PHP file
if [[ "$1" == *.php ]]; then
    echo -e "${YELLOW}📋 CATAPULT DOCUMENTATION STANDARDS REQUIRED:${NC}"
    echo "• PSR-12 Compliance: Complete class and method PHPDoc blocks"
    echo "• Agricultural Business Context: Comprehensive microgreens production workflows"
    echo "• Change Documentation: New/modified code must have proper documentation"
    echo "• Integration Context: Explain how code fits into agricultural workflows"
    echo "• Parameter Documentation: Complete @param and @return annotations"
    echo ""
    
    echo -e "${GREEN}✅ RECOMMENDED ACTION:${NC}"
    echo "Launch enhanced-documentation-auditor agent to validate and enhance documentation:"
    echo ""
    echo -e "${BLUE}Agent Prompt:${NC}"
    echo "Please review and enhance the documentation for: $1"
    echo ""
    echo "VALIDATION REQUIREMENTS:"
    echo "1. Verify PSR-12 compliance with comprehensive agricultural business context"
    echo "2. Ensure new/modified methods have complete agricultural workflow explanations"
    echo "3. Check all @param and @return annotations are comprehensive" 
    echo "4. Validate integration context explains agricultural business purpose"
    echo "5. Confirm file meets Catapult's comprehensive documentation standards"
    echo ""
    echo -e "${YELLOW}⚠️  This file should be documented before proceeding with additional changes${NC}"
else
    echo "Non-PHP file - no documentation validation needed"
fi

echo "────────────────────────────────────────────────────"