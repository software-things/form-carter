<?php

if(!file_exists('config.php')){
    echo "Creating up config.php file. Edit it to set up your configuration.";
    copy('config.php.dist', 'config.php');
} else {
    echo "config.php file already exists.";
}
