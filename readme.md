# Sample website in PHP
Because I do a lot of side projects all the time, I created this repo in order to be able to quickly run a php website from anywhere I want with the least amount of software installed.

Feel free to use it :)

## What's included
- Slim Framework installed (http://www.slimframework.com/)
- A customisable "404 not found" page
- Twig engine with all the templates located in the `src/templates` folder
- PSR-4 implementation (use of namespaces)

## Prerequisite
You need the following programs installed :
- php (http://php.net/manual/en/install.php)
- git (https://git-scm.com/book/en/v2/Getting-Started-Installing-Git)

## Getting started
Go in your terminal, and type the following commands :
```bash
git clone https://github.com/eurakilon/sample-website.git
cd sample-website
php init.php
php -S localhost:80
```

Then go to http://localhost to see the welcoming page

## Troubleshooting
### "Failed to listen on localhost:80"
**On Windows**, you can type `netstat -anb | findstr :80` in your command prompt to see the programm using your 80 port, and then `tskill [name of the bothering program]` in order to kill the program bothering you.

**On Linux**, you can type `sudo netstat -nlp | grep :80`, then look for the last number you find at the end of the line and type `kill -9 [number you just found]`

**If that's too complex for you**, you can simply type `php -S localhost:[another number than 80, like 8080 or 8888] -t .` instead of `php -S localhost:80`
