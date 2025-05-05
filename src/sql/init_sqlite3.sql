create table _enum_user_role (
    description varchar(50) not null primary key
);
insert into _enum_user_role (description) values ('admin');
insert into _enum_user_role (description) values ('normal_user');

create table users (
    id_user integer primary key not null,
    user_role varchar(50) not null,
    email varchar(200) not null unique,
    password_hash text not null,
    username varchar(100) default null,
    profile_picture varchar(255) default null,
    backup_email varchar(200) default null,
    pending_backup_email varchar(200) default null,
    backup_email_verification_token text default null,
    backup_email_verified_at datetime default null,
    last_user_update time not null default current_timestamp,
    foreign key (user_role) references _enum_user_role (description)
);

create table user_emails (
    id integer primary key not null,
    user_id integer not null,
    email varchar(200) not null unique,
    is_verified integer not null default 0,
    is_default integer not null default 0,
    is_pending_deletion integer not null default 0,
    verification_token text default null,
    deletion_token text default null,
    foreign key (user_id) references users (id_user)
);

-- admin account (admin@yopmail.com:adminadmin)
insert into users (email, password_hash, user_role) values ('admin@yopmail.com', '$2y$12$yD0xPh9jOWRlxklhM9oyrunlWElZDSVrp/OgmClTrWYE09l/3u/le', 'admin');
