use :db_name;

-- admin account (admin_php_starter@yopmail.com:admin)
select new_user('admin_php_starter@yopmail.com', '$2y$12$hA2wxJZhBLdHPJPQHQA.2e.sSUOqI/HAndSH8/9LD9WHn.cZ8qfz2', 'admin');

select 'Admin created';