drop table if exists _enum_user_role;
create table _enum_user_role (
    description varchar(50) not null primary key
);
insert into _enum_user_role (description) values ('admin');
insert into _enum_user_role (description) values ('normal_user');

drop table if exists users;
CREATE TABLE users (
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
    FOREIGN KEY (user_role) REFERENCES _enum_user_role (description)
);

drop table if exists user_emails;
create table user_emails (
    id integer primary key not null,
    user_id integer not null,
    email varchar(200) not null unique,
    is_verified integer not null default 0,
    is_default integer not null default 0,
    is_pending_deletion integer not null default 0,
    verification_token VARCHAR(255) NULL,
    deletion_token VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id_user) ON DELETE CASCADE
);

drop table if exists app_config;
create table app_config (
    id integer primary key not null,
    config_key varchar(255) not null,
    config_value varchar(255) not null
);
insert into app_config (config_key, config_value) values ('admin_password_is_strong', '0');
insert into app_config (config_key, config_value) values ('app_name', 'My App');
insert into app_config (config_key, config_value) values ('password_min_length', '8');

-- admin account (admin:admin)
insert into users (email, username, password_hash, user_role) values ('admin@yopmail.com', 'admin', '$2y$12$TsWyfj6Ztaqow/fu6PFQPOHiABBFRx1Phawy1vl9/PS3cZppJedwW', 'admin');
