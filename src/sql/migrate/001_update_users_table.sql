-- Add username and profile_picture columns to users table if they don't exist
PRAGMA foreign_keys=off;

BEGIN TRANSACTION;

-- Create a temporary table with the new structure
CREATE TABLE users_new (
    id_user INTEGER PRIMARY KEY NOT NULL,
    user_role VARCHAR(50) NOT NULL,
    email VARCHAR(200) DEFAULT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    username VARCHAR(100) DEFAULT NULL,
    profile_picture VARCHAR(255) DEFAULT NULL,
    last_user_update TIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_role) REFERENCES _enum_user_role(description)
);

-- Copy data from the old table to the new one
INSERT INTO users_new (id_user, user_role, email, password_hash, last_user_update)
SELECT id_user, user_role, email, password_hash, last_user_update FROM users;

-- Drop the old table
DROP TABLE users;

-- Rename the new table to the original table name
ALTER TABLE users_new RENAME TO users;

COMMIT;

PRAGMA foreign_keys=on; 