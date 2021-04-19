# Sample website in PHP
Because I do a lot of side projects all the time, I created this repo in order to be able to quickly run a php website from anywhere I want with the least amount of software installed.

Feel free to use it :)

## What's included
- Slim V4 PHP Framework
- Twig engine with all the templates located in the `src/templates` folder
- Bootstrap V5 (CSS + JS)

Also, once `.env` is configured (see `.default.env` for an example), you have the following utilities available as well :
- Basic signin system (without the signup part)
- JWT encode / decode functions
- Email utilities

## Getting started
```bash
php composer-update.php
```
(This project is managed with Composer, this script will download a local version of `composer.phar`, install all the dependencies required, and delete the composer file right after it)

After you installed the PHP libraries, you need to complete the `.env` file with the Database informations, the email informations and a JWT key. There is a `.default.env` file that you can copy and edit

After you have created a database, you can initialize it by running the 1 - 2 scripts in `admin_script.php`
```bash
php admin_script.php
```

Once you have installed everything, you can run the project with :
```bash
./start.cmd
```

## Troubleshooting
### "Failed to listen on localhost:80"
**On Windows**, you can type `netstat -anb | findstr :80` in your command prompt to see the programm using your 80 port, and then `tskill [name of the bothering program]` in order to kill the program bothering you.

**On Linux**, you can type `sudo netstat -nlp | grep :80`, then look for the last number you find at the end of the line and type `kill -9 [number you just found]`

**If that's too complex for you**, you can simply type `php -S localhost:[another number than 80, like 8080 or 8888] -t .` instead of `php -S localhost:80`
