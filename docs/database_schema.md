# Cursor AI Database Schema Publishing Instructions

## Objective
Maintain an up-to-date Markdown file that documents the database schema and automatically updates it whenever the schema is altered.

## Instructions for Cursor AI

1. **Create or Identify the Schema File**:
   - Create a file named `database_schema.md` in the project root directory if it does not already exist.
   - If the file exists, read its current content to prepare for updates.

2. **Generate the Database Schema Content**:
   - Extract the current database schema from the database management system (e.g., SQL database, ORM models, or schema definition files).
   - Format the schema in a clear, structured Markdown format. Include:
     - **Table Name**: Clearly state the name of each table.
     - **Columns**: List column names, data types, constraints (e.g., primary key, foreign key, nullable), and default values.
     - **Relationships**: Describe foreign key relationships or other dependencies between tables.
     - **Indexes**: Note any indexes defined on the tables.
     - **Example**:
       ```markdown
       # Database Schema

       ## Table: users
       - **id**: INTEGER, PRIMARY KEY, AUTO_INCREMENT
       - **username**: VARCHAR(50), NOT NULL, UNIQUE
       - **email**: VARCHAR(100), NOT NULL
       - **created_at**: TIMESTAMP, DEFAULT CURRENT_TIMESTAMP

       **Indexes**:
       - idx_username: UNIQUE ON username

       **Relationships**:
       - None

       ## Table: orders
       - **order_id**: INTEGER, PRIMARY KEY, AUTO_INCREMENT
       - **user_id**: INTEGER, NOT NULL
       - **order_date**: DATE, NOT NULL

       **Indexes**:
       - idx_user_id: ON user_id

       **Relationships**:
       - user_id FOREIGN KEY REFERENCES users(id) ON DELETE CASCADE

3. **Detect Schema Changes**:
   - Monitor schema files (e.g., migrations, ORM, SQL DDL) for changes.
   - Use git hooks or triggers to detect alterations.
   - Compare current schema with `database_schema.md` to find differences.

4. **Update the Schema File**:
   - On schema change (e.g., new table, altered column):
     - Regenerate schema content per step 2.
     - Overwrite `database_schema.md` with updated schema.
     - Keep formatting consistent.

5. **Publish the Schema File**:
   - Commit `database_schema.md` to version control (e.g., git).
   - Push changes to the remote repository.
   - Trigger documentation build/deployment if applicable.

6. **Error Handling**:
   - If schema extraction fails (e.g., DB connection issue), log error and notify team (e.g., Slack, email).
   - Retry file write/commit up to 3 times, then log error and notify team.

7. **Frequency**:
   - Check for changes on migrations, schema-related commits, or daily.
   - Update in real-time or near real-time for current documentation.

8. **File Location**:
   - Store `database_schema.md` in project root or `docs/` folder, per team agreement.
   - Ensure it's included in the repository and not in `.gitignore`.

## Notes
- Ensure schema is readable and follows Markdown best practices.
- For non-SQL databases (e.g., MongoDB), adapt format for collections/documents.
- Confirm format and location with the development team.