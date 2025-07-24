

1. Find the newest tasks-*.md file in docs/tasks/, if none exist, inform user to run generate-task-file first.
2. Open the newest tasks-*.md file.
3. Run "git status --porcelain" to list changed, added, or deleted files.
4. For each change not represented in the tasks-*.md file, append a new task and mark it "[X]".
5. Replace every remaining "[ ]" with "[X]".
6. Save the tasks-*.md file.
