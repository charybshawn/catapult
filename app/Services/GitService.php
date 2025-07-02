<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class GitService
{
    /**
     * Get the current git branch name
     */
    public static function getCurrentBranch(): string
    {
        try {
            $branch = trim(shell_exec('git rev-parse --abbrev-ref HEAD 2>/dev/null'));
            
            if (empty($branch) || $branch === 'HEAD') {
                return 'unknown';
            }
            
            return $branch;
        } catch (\Exception $e) {
            Log::warning('Failed to get git branch: ' . $e->getMessage());
            return 'unknown';
        }
    }

    /**
     * Check if we're in a git repository
     */
    public static function isGitRepository(): bool
    {
        try {
            $result = shell_exec('git rev-parse --is-inside-work-tree 2>/dev/null');
            return trim($result) === 'true';
        } catch (\Exception $e) {
            return false;
        }
    }
}