SET @db_name = ifnull(getenv('DB_NAME'), ':db_name');

drop database if exists @db_name;
create database @db_name default character set utf8mb4 collate utf8mb4_general_ci;
use @db_name;

create or replace table _enum_user_role (
    description varchar(50) not null primary key
);
insert into _enum_user_role (description) values ('admin');
insert into _enum_user_role (description) values ('normal_user');

create or replace table users (
    id_user int(11) primary key not null auto_increment,
    user_role varchar(50) not null,
    email varchar(200) default null unique,
    password_hash text not null,
    username varchar(100) not null unique,
    profile_picture varchar(255) default null,
    last_user_update time not null default current_timestamp(),
    constraint
    foreign key (user_role) references _enum_user_role (description)
);

-- admin account (admin@yopmail.com:adminadmin)
insert into users (email, password_hash, user_role) values ('admin@yopmail.com', '$2y$12$yD0xPh9jOWRlxklhM9oyrunlWElZDSVrp/OgmClTrWYE09l/3u/le', 'admin');

select 'Query done';
