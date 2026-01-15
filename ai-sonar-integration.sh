#!/bin/bash
# AI Code Detection Integration Script for SonarQube
# Usage: ./ai-sonar-integration.sh [options]

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROJECT_DIR="${PROJECT_DIR:-.}"
SONAR_HOST="${SONAR_HOST:-http://localhost:9000}"
SONAR_TOKEN="${SONAR_TOKEN:-}"
ANALYZER_SCRIPT="ai-code-analyzer.php"
PATTERNS_FILE=".ai-patterns.json"

echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║        AI Code Detection for SonarQube - Integration       ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Function to check prerequisites
check_prerequisites() {
    echo -e "${YELLOW}[1/5] Checking prerequisites...${NC}"
    
    # Check PHP
    if ! command -v php &> /dev/null; then
        echo -e "${RED}✗ PHP is not installed${NC}"
        exit 1
    fi
    echo -e "${GREEN}✓ PHP found: $(php -v | head -n1)${NC}"
    
    # Check analyzer script
    if [ ! -f "$PROJECT_DIR/$ANALYZER_SCRIPT" ]; then
        echo -e "${RED}✗ AI analyzer script not found: $ANALYZER_SCRIPT${NC}"
        exit 1
    fi
    echo -e "${GREEN}✓ AI analyzer script found${NC}"
    
    # Check patterns file
    if [ ! -f "$PROJECT_DIR/$PATTERNS_FILE" ]; then
        echo -e "${RED}✗ Patterns file not found: $PATTERNS_FILE${NC}"
        exit 1
    fi
    echo -e "${GREEN}✓ Patterns file found${NC}"
    
    echo ""
}

# Function to run AI analysis
run_ai_analysis() {
    echo -e "${YELLOW}[2/5] Running AI code analysis...${NC}"
    
    php "$PROJECT_DIR/$ANALYZER_SCRIPT" "$PROJECT_DIR/src"
    
    echo ""
}

# Function to generate SonarQube report
generate_sonar_report() {
    echo -e "${YELLOW}[3/5] Generating SonarQube compatible report...${NC}"
    
    php "$PROJECT_DIR/$ANALYZER_SCRIPT" "$PROJECT_DIR/src" --format=sonar > "sonar-ai-report.json" 2>/dev/null || \
    php "$PROJECT_DIR/$ANALYZER_SCRIPT" "$PROJECT_DIR/src" > "sonar-ai-report.json"
    
    if [ -f "sonar-ai-report.json" ]; then
        echo -e "${GREEN}✓ Report generated: sonar-ai-report.json${NC}"
        echo ""
        echo "Report Summary:"
        echo "================"
        head -50 "sonar-ai-report.json"
    else
        echo -e "${YELLOW}! Report generation had issues${NC}"
    fi
    
    echo ""
}

# Function to integrate with SonarQube
integrate_sonar() {
    echo -e "${YELLOW}[4/5] Integrating with SonarQube...${NC}"
    
    if [ -n "$SONAR_TOKEN" ]; then
        # Update sonar-project.properties
        if [ -f "$PROJECT_DIR/sonar-project.properties" ]; then
            # Add AI detection rules
            echo "" >> "$PROJECT_DIR/sonar-project.properties"
            echo "# AI Detection Configuration" >> "$PROJECT_DIR/sonar-project.properties"
            echo "sonar.ai.detection.enabled=true" >> "$PROJECT_DIR/sonar-project.properties"
            echo "sonar.ai.rules.file=sonar-rules-ai.xml" >> "$PROJECT_DIR/sonar-project.properties"
            echo "sonar.ai.quality.profile=quality-profiles/ai-detection.xml" >> "$PROJECT_DIR/sonar-project.properties"
            echo -e "${GREEN}✓ Updated sonar-project.properties${NC}"
        fi
        
        # Run SonarQube scanner with AI rules
        if command -v sonar-scanner &> /dev/null; then
            echo -e "${YELLOW}Running SonarQube scanner...${NC}"
            sonar-scanner \
                -Dsonar.host.url="$SONAR_HOST" \
                -Dsonar.login="$SONAR_TOKEN" \
                -Dsonar.projectKey="ai-detection-project" \
                -Dsonar.sources=src \
                -Dsonar.php.exclusions=vendor/** \
                -Dsonar.ai.detection.enabled=true
            echo -e "${GREEN}✓ SonarQube scan completed${NC}"
        else
            echo -e "${YELLOW}! Sonar-scanner not found, skipping${NC}"
        fi
    else
        echo -e "${YELLOW}! SONAR_TOKEN not set, skipping SonarQube integration${NC}"
        echo "   Set SONAR_TOKEN environment variable to enable"
    fi
    
    echo ""
}

# Function to display results
display_results() {
    echo -e "${YELLOW}[5/5] Analysis Complete!${NC}"
    echo ""
    
    echo -e "${BLUE}Files Created:${NC}"
    echo "  • ai-code-analyzer.php - Main analysis script"
    echo "  • .ai-patterns.json - AI detection patterns database"
    echo "  • sonar-rules-ai.xml - SonarQube custom rules"
    echo "  • quality-profiles/ai-detection.xml - Quality profile"
    echo "  • phpstan.neon - PHPStan configuration"
    echo ""
    
    echo -e "${BLUE}Usage:${NC}"
    echo "  # Run AI analysis on src directory:"
    echo "  php ai-code-analyzer.php src"
    echo ""
    echo "  # Run with SonarQube:"
    echo "  SONAR_TOKEN=<token> ./ai-sonar-integration.sh"
    echo ""
    
    echo -e "${BLUE}What It Detects:${NC}"
    echo "  [Patterns]              [Errors]"
    echo "  • Template comments     • SQL Injection"
    echo "  • Generic variables     • XSS Vulnerabilities"
    echo "  • Magic numbers         • Empty try-catch"
    echo "  • Long method chains    • Type juggling"
    echo "  • High comment ratio    • Missing strict types"
    echo ""
    
    echo -e "${GREEN}✓ AI Code Detection setup complete!${NC}"
}

# Parse command line arguments
case "${1:-}" in
    --check|-c)
        check_prerequisites
        exit 0
        ;;
    --help|-h)
        echo "Usage: $0 [options]"
        echo ""
        echo "Options:"
        echo "  --check, -c     Check prerequisites only"
        echo "  --help, -h      Show this help message"
        echo ""
        echo "Environment Variables:"
        echo "  SONAR_HOST      SonarQube server URL (default: http://localhost:9000)"
        echo "  SONAR_TOKEN     SonarQube authentication token"
        echo "  PROJECT_DIR     Project directory (default: current directory)"
        exit 0
        ;;
esac

# Main execution
check_prerequisites
run_ai_analysis
generate_sonar_report
integrate_sonar
display_results

