<?php
if (!file_exists('composer.phar')) {
    echo "> composer.phar not found, downloading it\n";
    copy('https://getcomposer.org/installer', 'composer-setup.php');
    exec('php composer-setup.php');
    unlink('composer-setup.php');
} else {
    echo "> composer.phar found\n";
}
exec('php composer.phar update -o');
die("\n-----------------------\n Composer update done !\n-----------------------");