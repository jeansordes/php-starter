# Sample website in PHP
Because I do a lot of side projects all the time, I created this repo in order to be able to quickly run a php website from anywhere I want with the least amount of software installed.

Feel free to use it :)

## Basic prerequisites
- PHP needed
- SQLite3 extension activated

## What's included
- Slim V4 PHP Framework
- Twig engine with all the templates located in the `src/templates` folder
- Bootstrap V5 (CSS + JS)
- Signin / signup / forgotten password
- `.env` configuration (https://github.com/symfony/dotenv) (there is a `.default.env` file that is automatically copied to make you a `.env` file when you run the app once)
- Email utilities (if `app_mode=prod` in the `.env` file, otherwise the emails are displayed on screen)
