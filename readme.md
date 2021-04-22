# Sample website in PHP
Because I do a lot of side projects all the time, I created this repo in order to be able to quickly run a php website from anywhere I want with the least amount of software installed.

Feel free to use it :)

## Basic prerequisites
- PHP needed (duh)
- SQLite3 extension activated

## What's included
- Slim V4 PHP Framework
- Twig engine with all the templates located in the `src/templates` folder
- Bootstrap V5 (CSS + JS)
- Signin / signup / forgotten password
- `.env` configuration (https://github.com/symfony/dotenv) (there is a `.default.env` file that is automatically copied to make you a `.env` file when you run the app once)
- Email utilities (if `app_mode=prod` in the `.env` file, otherwise the emails are displayed on screen)
- JWT encode / decode functions
- MariaDB if `.env` is configured accordingly

## Getting started
```bash
php composer-update.php
```
(This project is managed with Composer, this script will download a local version of `composer.phar`, install all the dependencies required, and delete the composer file right after it)

Once you have installed everything, you can run the project with :
```bash
./start.cmd
```

## Troubleshooting
### "Failed to listen on localhost:80"
**On Windows**, you can type `netstat -anb | findstr :80` in your command prompt to see the programm using your 80 port, and then `tskill [name of the bothering program]` in order to kill the program bothering you.

**On Linux**, you can type `sudo netstat -nlp | grep :80`, then look for the last number you find at the end of the line and type `kill -9 [number you just found]`

**If that's too complex for you**, you can simply type `php -S localhost:[another number than 80, like 8080 or 8888] -t .` instead of `php -S localhost:80`
