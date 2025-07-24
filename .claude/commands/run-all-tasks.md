1. Find the newest tasks-*.md file in docs/tasks/, if none exist, inform user to run generate-task-file first.
2. Read the newest tasks-*.md file.
3. Find the first "[ ]" line (uncompleted task).
4. If no "[ ]" tasks found, inform user all tasks are complete and stop.
5. Ask Claude to implement ONLY that single task.
6. On success, replace "[ ]" with "[X]" for that specific line.
7. Save the tasks-*.md file.
8. Immediately repeat from step 2 to find the next uncompleted task.
9. Continue this loop until no more "[ ]" tasks remain.

Note: Each task is completed individually with full focus. Claude should not use todo lists or batch processing - just implement the single task found, mark it complete, then move to the next one.
