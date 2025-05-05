PRAGMA foreign_keys=off;

BEGIN TRANSACTION;

ALTER TABLE user_emails ADD COLUMN is_pending_deletion INTEGER DEFAULT 0;
ALTER TABLE user_emails ADD COLUMN deletion_token VARCHAR(255) NULL;

COMMIT;

PRAGMA foreign_keys=on; 
