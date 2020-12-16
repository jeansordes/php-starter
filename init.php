<?php
copy('https://getcomposer.org/installer', 'composer-setup.php');
exec('php composer-setup.php');
unlink('composer-setup.php');
exec('php composer.phar update -o');
die("\n-----------------------\n      Init done !!\n-----------------------");