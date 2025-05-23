# Git Workflow Guidelines

This document outlines our standard Git workflow using the GitFlow branching strategy. Following these guidelines ensures a consistent approach to development and helps maintain a stable codebase.

## Branch Structure

Our repository uses the following branch structure:

- **main**: Production-ready code that is currently deployed
- **develop**: Integration branch for features in development
- **feature/\***: Individual feature development
- **release/\***: Preparation for new releases
- **hotfix/\***: Urgent fixes for production issues

## Workflow for New Features

When developing a new feature:

1. **Start from develop branch**:
   ```bash
   git checkout develop
   git pull origin develop  # Ensure you have the latest changes
   ```

2. **Create a feature branch**:
   ```bash
   git checkout -b feature/your-feature-name
   ```
   Use a descriptive name like `feature/user-authentication` or `feature/product-filter`.

3. **Work on your feature**:
   Make commits regularly with clear commit messages:
   ```bash
   git add .
   git commit -m "Add user login form"
   ```

4. **Push to remote repository**:
   ```bash
   git push origin feature/your-feature-name
   ```

5. **Create a Pull Request**:
   - Target branch: `develop`
   - Provide a clear description of the changes
   - Request reviews from team members

6. **After approval, merge to develop**:
   This can be done through the GitHub interface or command line:
   ```bash
   git checkout develop
   git merge feature/your-feature-name
   git push origin develop
   ```

7. **Clean up**:
   ```bash
   git branch -d feature/your-feature-name  # Delete local branch
   git push origin --delete feature/your-feature-name  # Delete remote branch
   ```

## Workflow for Releases

When preparing a release:

1. **Create a release branch from develop**:
   ```bash
   git checkout develop
   git pull origin develop
   git checkout -b release/v1.x.x
   ```
   Use semantic versioning (e.g., `release/v1.2.0`).

2. **Finalize the release**:
   - Fix any bugs specific to the release
   - Update version numbers in relevant files
   - Update documentation

3. **Merge to main**:
   ```bash
   git checkout main
   git pull origin main
   git merge release/v1.x.x
   git push origin main
   ```

4. **Tag the release**:
   ```bash
   git tag -a v1.x.x -m "Version 1.x.x"
   git push origin v1.x.x
   ```

5. **Merge back to develop**:
   ```bash
   git checkout develop
   git pull origin develop
   git merge release/v1.x.x
   git push origin develop
   ```

6. **Clean up**:
   ```bash
   git branch -d release/v1.x.x
   git push origin --delete release/v1.x.x
   ```

## Workflow for Hotfixes

When fixing an urgent production issue:

1. **Create a hotfix branch from main**:
   ```bash
   git checkout main
   git pull origin main
   git checkout -b hotfix/critical-fix
   ```

2. **Implement the fix**:
   ```bash
   git add .
   git commit -m "Fix critical issue"
   ```

3. **Merge to main**:
   ```bash
   git checkout main
   git pull origin main
   git merge hotfix/critical-fix
   git push origin main
   ```

4. **Tag the new version**:
   ```bash
   git tag -a v1.x.y -m "Hotfix version 1.x.y"
   git push origin v1.x.y
   ```

5. **Merge to develop as well**:
   ```bash
   git checkout develop
   git pull origin develop
   git merge hotfix/critical-fix
   git push origin develop
   ```

6. **Clean up**:
   ```bash
   git branch -d hotfix/critical-fix
   git push origin --delete hotfix/critical-fix
   ```

## Best Practices

- **Pull regularly**: Always pull before starting new work
- **Commit often**: Make small, focused commits
- **Write meaningful commit messages**: Describe what and why (not how)
- **Rebase feature branches**: Keep your feature branch up to date with develop
- **Delete merged branches**: Keep the repository clean
- **Never push directly to main or develop**: Always use feature branches and pull requests

## Common Git Commands

```bash
# View all branches
git branch -a

# Discard local changes
git checkout -- .

# Update feature branch with changes from develop
git checkout feature/your-feature
git rebase develop

# View commit history
git log --oneline --graph --decorate

# Undo last commit but keep changes
git reset --soft HEAD^

# Stash changes temporarily
git stash
git stash pop

# Check differences
git diff develop..feature/your-feature
``` 