-- Add a unique constraint to the username column in the users table

PRAGMA foreign_keys=off;

BEGIN TRANSACTION;

-- Create a temporary table with the new structure
CREATE TABLE users_new (
    id_user INTEGER PRIMARY KEY NOT NULL,
    user_role VARCHAR(50) NOT NULL,
    email VARCHAR(200) DEFAULT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    profile_picture VARCHAR(255) DEFAULT NULL,
    backup_email VARCHAR(200) DEFAULT NULL,
    pending_backup_email VARCHAR(200) DEFAULT NULL,
    backup_email_verification_token TEXT DEFAULT NULL,
    backup_email_verified_at DATETIME DEFAULT NULL,
    last_user_update TIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_role) REFERENCES _enum_user_role(description)
);

-- Copy data from the old table to the new one
INSERT INTO users_new SELECT * FROM users;

-- Drop the old table
DROP TABLE users;

-- Rename the new table to the original table name
ALTER TABLE users_new RENAME TO users;

COMMIT;

PRAGMA foreign_keys=on;
