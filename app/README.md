# Application Core

This directory contains the core application logic of the PHP application.

## Directory Structure

- `routes/` - Contains all route definitions
- `templates/` - Contains Twig templates for rendering views
- `utilities.php` - Common utility functions
- `slim-config.php` - Slim framework configuration
- `sql-utilities.php` - Database utility functions
- `EditableException.php` - Custom exception class

## Security

The `.htaccess` file in this directory denies direct access to these files when deployed on an Apache server. All requests should go through the public entry point. 