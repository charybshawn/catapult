# Git Procedures for Catapult v2

## Overview
This document outlines our Git workflow for the Catapult v2 project. We follow a simplified version of the Git Flow workflow, focusing on safety and simplicity.

## Initial Setup

### 1. Create GitHub Repository
1. Go to GitHub.com and sign in
2. Click "New" to create a new repository
3. Name it "catapult_v2"
4. Keep it Private
5. Don't initialize with README (we already have one)
6. Copy the repository URL (looks like `git@github.com:username/catapult_v2.git`)

### 2. Initial Setup in Cursor
```bash
# Initialize Git repository (if not done)
git init

# Add .gitignore for Laravel
curl -o .gitignore https://raw.githubusercontent.com/laravel/laravel/master/.gitignore

# Add additional entries to .gitignore
echo "
.env
.env.backup
.phpunit.result.cache
docker-compose.override.yml
/.idea
/.vscode
" >> .gitignore

# Initial commit
git add .
git commit -m "Initial commit: Project setup"

# Connect to GitHub repository
git remote add origin YOUR_GITHUB_REPO_URL
git branch -M main
git push -u origin main
```

### 3. Verify Setup
```bash
# Verify remote connection
git remote -v

# Should show:
# origin  git@github.com:username/catapult_v2.git (fetch)
# origin  git@github.com:username/catapult_v2.git (push)
```

### 4. Setup GitHub Authentication
1. If using HTTPS:
   - You'll need to enter your GitHub credentials
   - Use a Personal Access Token instead of password
   
2. If using SSH (recommended):
   ```bash
   # Check for existing SSH keys
   ls -al ~/.ssh
   
   # Generate new SSH key if needed
   ssh-keygen -t ed25519 -C "your_email@example.com"
   
   # Add key to ssh-agent
   eval "$(ssh-agent -s)"
   ssh-add ~/.ssh/id_ed25519
   
   # Copy public key
   pbcopy < ~/.ssh/id_ed25519.pub
   ```
   - Go to GitHub.com → Settings → SSH Keys
   - Add new SSH key
   - Paste your key and save

### 5. Protect Main Branch (Recommended)
1. Go to GitHub repository settings
2. Navigate to Branches
3. Add branch protection rule for `main`
4. Enable:
   - Require pull request reviews
   - Dismiss stale pull request approvals
   - Require status checks to pass
   - Require branches to be up to date

## Basic Rules
1. Never commit directly to the `main` branch
2. Always work in feature branches
3. Commit often with clear messages
4. Pull latest changes before starting new work

## Daily Workflow

### 1. Starting Your Day
```bash
# Make sure you're on main branch
git checkout main

# Get latest changes
git pull origin main
```

### 2. Creating a New Feature
```bash
# Create and switch to a new branch
git checkout -b feature/descriptive-name

# Example branch names:
# feature/recipe-management
# feature/inventory-tracking
# fix/login-error
```

### 3. During Development
```bash
# Check what files you've changed
git status

# Stage specific files
git add filename.php

# Stage all changes (use carefully)
git add .

# Commit your changes
git commit -m "Clear description of what you did"

# Example commit messages:
# "Add recipe creation form"
# "Fix inventory calculation bug"
# "Update user authentication logic"
```

### 4. Saving Your Work
```bash
# Push your changes to GitHub
git push origin feature/descriptive-name
```

### 5. Completing a Feature
1. Go to GitHub
2. Create a Pull Request from your feature branch to `main`
3. Review the changes
4. Merge if everything looks good

## Commit Message Guidelines
- Start with a verb (Add, Update, Fix, Refactor)
- Be specific but concise
- Describe what and why, not how

Good examples:
- "Add water schedule tracking to recipe form"
- "Fix inventory calculation for mixed greens"
- "Update user permissions for inventory management"

## Safety Tips
1. Before any major change:
   ```bash
   git checkout -b backup/before-big-change
   ```

2. If you make a mistake:
   ```bash
   # Undo last commit but keep changes
   git reset --soft HEAD^

   # Completely undo last commit
   git reset --hard HEAD^
   ```

3. If you're stuck:
   ```bash
   # Save work in progress
   git stash

   # Return to clean state
   git checkout main

   # Later, get your work back
   git stash pop
   ```

## Automated Git Commands in Cursor
- Cursor can help stage changes and create commits
- However, you should still review changes before committing
- Manual pushing to GitHub is recommended for safety

## Branch Naming Convention
- `feature/` - For new features
- `fix/` - For bug fixes
- `update/` - For updates to existing features
- `docs/` - For documentation changes

## Getting Help
If you're ever unsure:
1. Check this document
2. Use `git --help` or `git command --help`
3. Ask for assistance before making major changes

## Common Scenarios

### 1. "I need to undo my last commit"
```bash
# Undo commit but keep changes
git reset --soft HEAD^
```

### 2. "I need to switch tasks but I'm not done"
```bash
# Save current work
git stash

# Switch branches
git checkout other-branch

# When ready to resume
git checkout original-branch
git stash pop
```

### 3. "I need to update my branch with main"
```bash
# Get latest main
git checkout main
git pull origin main

# Update your branch
git checkout your-branch
git merge main
```

## Weekly Maintenance
1. Clean up merged branches
2. Review open branches
3. Ensure main branch is stable

Remember: When in doubt, it's better to ask for help than to try to fix Git problems alone! 