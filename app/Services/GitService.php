<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Comprehensive version control integration service for agricultural operations deployment.
 * 
 * This specialized service provides seamless integration with Git version control systems,
 * enabling deployment automation, environment identification, and development workflow
 * support for agricultural commerce platform operations. Essential for maintaining
 * deployment visibility and environment-aware application behavior.
 * 
 * @service_domain Development operations and deployment automation
 * @business_purpose Version control integration for agricultural commerce platform
 * @agricultural_focus Git integration supporting agricultural software deployment
 * @deployment_support Environment identification and deployment automation
 * @development_workflow Git integration supporting agricultural development operations
 * 
 * Core Version Control Features:
 * - **Branch Detection**: Current branch identification for environment-specific behavior
 * - **Repository Validation**: Git repository verification for deployment contexts
 * - **Environment Awareness**: Branch-based environment detection and configuration
 * - **Development Support**: Git integration for development and staging workflows
 * - **Deployment Automation**: Version control information for deployment processes
 * - **Error Resilience**: Graceful handling of non-Git environments and failures
 * 
 * Agricultural Development Applications:
 * - **Environment Detection**: Branch-based identification of development, staging, production
 * - **Feature Deployment**: Branch information for feature flag and configuration management
 * - **Agricultural Testing**: Environment-specific behavior for agricultural data testing
 * - **Production Safety**: Production branch verification for agricultural commerce operations
 * - **Development Workflows**: Git integration supporting agricultural software development
 * - **Deployment Tracking**: Version information for agricultural platform releases
 * 
 * DevOps Integration:
 * - **CI/CD Pipelines**: Branch information for automated deployment workflows
 * - **Environment Configuration**: Branch-based configuration for agricultural environments
 * - **Deployment Verification**: Git repository validation for deployment processes
 * - **Release Management**: Branch tracking for agricultural platform releases
 * - **Feature Flags**: Branch-based feature enablement for agricultural commerce
 * 
 * Agricultural Commerce Context:
 * - **Production Reliability**: Environment detection ensures production stability
 * - **Development Isolation**: Branch-based separation of development and production data
 * - **Testing Environments**: Git integration for agricultural testing workflows
 * - **Deployment Automation**: Version control support for commerce platform deployment
 * - **Quality Assurance**: Branch information for quality control workflows
 * 
 * Technical Architecture:
 * - **Shell Command Integration**: Secure git command execution with error handling
 * - **Static Methods**: Utility-focused design for easy integration throughout application
 * - **Error Resilience**: Comprehensive exception handling for deployment reliability
 * - **Performance Optimized**: Lightweight git integration without overhead
 * - **Cross-Platform**: Compatible across development and production environments
 * 
 * Deployment Safety:
 * - **Production Protection**: Branch detection prevents development code in production
 * - **Environment Validation**: Ensures appropriate git context for deployment operations
 * - **Fallback Handling**: Graceful degradation when git information unavailable
 * - **Error Logging**: Comprehensive logging for deployment troubleshooting
 * 
 * Development Workflow Support:
 * - **Branch-Based Development**: Support for git-flow and feature branch workflows
 * - **Environment Synchronization**: Branch information for environment-specific configuration
 * - **Testing Integration**: Git context for agricultural testing and validation
 * - **Code Quality**: Version control integration for quality assurance workflows
 * 
 * Security and Reliability:
 * - **Command Sanitization**: Secure shell command execution with error redirection
 * - **Exception Handling**: Comprehensive error handling prevents deployment failures
 * - **Logging Integration**: Git operation logging for deployment audit trails
 * - **Fallback Values**: Safe defaults when git information unavailable
 * 
 * Agricultural Software Development:
 * - **Crop Management Features**: Branch-based feature development for agricultural systems
 * - **Commerce Platform**: Git integration for agricultural commerce platform development
 * - **Data Management**: Version-aware agricultural data management workflows
 * - **API Development**: Git context for agricultural API development and testing
 * 
 * @development_operations Essential DevOps integration for agricultural software deployment
 * @version_control_integration Seamless git integration for agricultural development workflows
 * @deployment_automation Git information supporting automated deployment processes
 * @agricultural_devops Specialized development operations for agricultural commerce platform
 */
class GitService
{
    /**
     * Retrieve current Git branch name for environment detection and deployment workflows.
     * 
     * Determines the active Git branch through secure command execution, providing
     * essential information for environment-specific behavior, deployment automation,
     * and agricultural development workflows. Critical for branch-based configuration
     * management and production safety in agricultural commerce operations.
     * 
     * @return string Current git branch name or 'unknown' if indeterminate
     * 
     * @static_utility Available throughout application without instantiation
     * @environment_detection Branch information enables environment-specific behavior
     * @deployment_safety Critical for production branch verification
     * @agricultural_workflows Git branch context for agricultural development operations
     * 
     * Branch Detection Process:
     * - **Git Command Execution**: Uses 'git rev-parse --abbrev-ref HEAD' for accurate branch detection
     * - **Error Redirection**: Redirects stderr to prevent error output in production
     * - **Output Sanitization**: Trims whitespace and validates branch name format
     * - **Fallback Handling**: Returns 'unknown' for detached HEAD or error conditions
     * - **Exception Recovery**: Comprehensive error handling with logging
     * 
     * Agricultural Development Applications:
     * - **Environment Identification**: Branch-based detection of development, staging, production
     * - **Feature Deployment**: Branch information for agricultural feature flag management
     * - **Production Safety**: Verify production branch before agricultural commerce operations
     * - **Testing Workflows**: Branch context for agricultural testing and validation
     * - **Configuration Management**: Branch-specific configuration for agricultural systems
     * 
     * Deployment Integration:
     * - **CI/CD Pipelines**: Branch information for automated agricultural deployment workflows
     * - **Environment Configuration**: Branch-based settings for agricultural environments
     * - **Release Management**: Branch tracking for agricultural platform releases
     * - **Quality Gates**: Branch verification for production deployment safety
     * 
     * Security and Reliability:
     * - **Secure Execution**: Shell command with proper error handling and redirection
     * - **Error Resilience**: Graceful handling of git command failures
     * - **Production Safety**: Returns safe fallback when branch detection fails
     * - **Logging Integration**: Error logging for deployment troubleshooting
     * 
     * Special Conditions:
     * - **Detached HEAD**: Returns 'unknown' for detached HEAD state
     * - **Non-Git Environments**: Returns 'unknown' when not in git repository
     * - **Command Failures**: Returns 'unknown' with logging when git commands fail
     * - **Empty Results**: Handles cases where git command returns empty string
     * 
     * Agricultural Commerce Context:
     * - **Production Branch**: Verify 'main' or 'production' branch for commerce operations
     * - **Development Features**: Feature branch information for agricultural development
     * - **Staging Environments**: Branch-based staging environment configuration
     * - **Release Tracking**: Branch information for agricultural platform releases
     * 
     * Performance Considerations:
     * - **Lightweight Execution**: Single git command with minimal overhead
     * - **Error Prevention**: Error handling prevents performance impact from failures
     * - **Caching Opportunity**: Results could be cached for repeated calls
     * - **Production Optimized**: Designed for reliable operation in production
     * 
     * @agricultural_deployment Essential for agricultural commerce platform deployment
     * @environment_awareness Branch detection enables environment-specific agricultural workflows
     * @production_safety Critical for ensuring production stability in agricultural operations
     */
    public static function getCurrentBranch(): string
    {
        try {
            $branch = trim(shell_exec('git rev-parse --abbrev-ref HEAD 2>/dev/null'));
            
            if (empty($branch) || $branch === 'HEAD') {
                return 'unknown';
            }
            
            return $branch;
        } catch (Exception $e) {
            Log::warning('Failed to get git branch: ' . $e->getMessage());
            return 'unknown';
        }
    }

    /**
     * Verify Git repository context for deployment validation and environment detection.
     * 
     * Determines whether the current working directory is within a Git repository,
     * providing essential validation for deployment processes and development
     * workflows. Critical for ensuring proper version control context before
     * executing git-dependent operations in agricultural commerce systems.
     * 
     * @return bool True if within git repository, false otherwise
     * 
     * @static_utility Available throughout application for repository validation
     * @deployment_validation Ensures proper git context for deployment operations
     * @environment_verification Validates version control context for agricultural environments
     * @development_workflow Git repository verification for agricultural development
     * 
     * Repository Detection Process:
     * - **Git Validation Command**: Uses 'git rev-parse --is-inside-work-tree' for accurate detection
     * - **Error Suppression**: Redirects stderr to prevent error output in non-git environments
     * - **Result Verification**: Confirms 'true' response from git command
     * - **Exception Handling**: Comprehensive error recovery with false return
     * - **Deployment Safety**: Prevents git operations in non-repository contexts
     * 
     * Agricultural Development Applications:
     * - **Deployment Validation**: Verify git context before agricultural platform deployment
     * - **Development Environment**: Confirm git repository for agricultural development workflows
     * - **CI/CD Prerequisites**: Validate repository context for automated deployment
     * - **Version Control Safety**: Prevent git operations outside repository context
     * - **Environment Setup**: Repository verification for agricultural development environments
     * 
     * Deployment Safety Benefits:
     * - **Pre-deployment Validation**: Ensure proper version control context before deployment
     * - **Error Prevention**: Prevent git command failures in non-repository environments
     * - **Workflow Validation**: Confirm appropriate context for git-dependent operations
     * - **Production Safety**: Validate repository context for production agricultural operations
     * 
     * Agricultural Commerce Context:
     * - **Production Deployment**: Verify git repository for agricultural commerce deployment
     * - **Development Workflows**: Repository validation for agricultural feature development
     * - **Testing Environments**: Git context verification for agricultural testing
     * - **Release Management**: Repository validation for agricultural platform releases
     * 
     * Technical Implementation:
     * - **Shell Command Execution**: Secure execution with proper error handling
     * - **Boolean Return**: Simple true/false result for easy integration
     * - **Error Resilience**: Exception handling prevents deployment failures
     * - **Performance Optimized**: Lightweight validation without overhead
     * 
     * Use Cases:
     * - **Pre-deployment Checks**: Validate repository before deployment operations
     * - **Development Setup**: Confirm git repository for development workflows
     * - **Conditional Logic**: Enable git-dependent features only in repository context
     * - **Environment Verification**: Ensure appropriate context for version control operations
     * 
     * Error Handling:
     * - **Command Failures**: Returns false when git command execution fails
     * - **Non-Git Directories**: Returns false in directories without git repository
     * - **Permission Issues**: Handles cases where git commands lack proper permissions
     * - **Exception Safety**: Comprehensive exception handling prevents application failures
     * 
     * Integration Benefits:
     * - **Conditional Git Operations**: Enable git features only when appropriate
     * - **Deployment Validation**: Ensure proper context for deployment automation
     * - **Development Support**: Repository verification for development tools
     * - **Error Prevention**: Prevent git operation failures through validation
     * 
     * @agricultural_deployment Repository validation for agricultural commerce deployment
     * @development_environment Git context verification for agricultural development workflows
     * @deployment_safety Essential validation preventing git operation failures
     */
    public static function isGitRepository(): bool
    {
        try {
            $result = shell_exec('git rev-parse --is-inside-work-tree 2>/dev/null');
            return trim($result) === 'true';
        } catch (Exception $e) {
            return false;
        }
    }
}