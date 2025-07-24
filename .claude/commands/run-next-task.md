

1. Find the newest tasks-*.md file in docs/tasks/, if none exist, inform user to run generate-task-file first.
2. Read the newest tasks-*.md file.
3. Find the first "[ ]" line (uncompleted task).
4. Ask Claude to implement that task only.
5. On success replace "[ ]" with "[X]" for that line.
6. Save the tasks-*.md file and then stop.

Example: If "- [ ] Add user validation" is found in tasks-v002.md, implement it then mark as "- [X] Add user validation"
