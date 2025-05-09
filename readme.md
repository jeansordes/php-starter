# PHP Starter Project

A PHP starter project with a modern structure that separates public files from application code.

## Project Structure

- `public/` - Document root containing the front controller and public assets
- `app/` - Application core with routes, templates, and business logic
- `vendor/` - Composer dependencies
- `db/` - Database migrations and setup scripts
- `scripts/` - Development and utility scripts
- `uploads/` - Directory for file uploads (not served directly)

## Setup

1. Clone the repository
2. Run `composer install`
3. Copy `.default.env` to `.env` and configure your environment variables
4. Run `composer start` to start the development server

## Development

- Use `composer start` to start the development server
- Use `composer db:rebuild` to rebuild the database

## Security

The project is structured to prevent direct access to sensitive files. Only files in the `public/` directory are directly accessible through the web server.

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
