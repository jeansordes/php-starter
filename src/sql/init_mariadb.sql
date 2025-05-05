drop database if exists :db_name;
create database :db_name default character set utf8mb4 collate utf8mb4_general_ci;
use :db_name;

create or replace table _enum_user_role (
    description varchar(50) not null primary key
);
insert into _enum_user_role(description) values ('admin');
insert into _enum_user_role(description) values ('normal_user');

create or replace table users (
    id_user int(11) primary key not null auto_increment,
    user_role varchar(50) not null,
    email varchar(200) default null unique,
    password_hash text not null,
    last_user_update time not null default current_timestamp(),
    constraint
        foreign key (user_role) references _enum_user_role(description)
);

-- admin account (admin_php_starter@yopmail.com:adminadmin)
insert into users(email, password_hash, user_role) values ('admin_php_starter@yopmail.com', '$2y$12$yD0xPh9jOWRlxklhM9oyrunlWElZDSVrp/OgmClTrWYE09l/3u/le', 'admin');

select 'Query done';