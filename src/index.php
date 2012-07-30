<?php

define('BASE_PATH', dirname(__FILE__));
define('FRAME_PATH', BASE_PATH.'/framework');
define('STATIC_PATH', BASE_PATH.'/static');
define('CTRL_PATH', BASE_PATH.'/controllers');
define('VIEW_PATH', BASE_PATH.'/views');
define('MODEL_PATH', BASE_PATH.'/models');
define('LIB_PATH', BASE_PATH.'/libraries');

function safe_dirname($path)
{
    $dirname = dirname($path);
    return $dirname == '/' ? '' : $dirname;
}

define('SCRIPT_URI', $_SERVER['SCRIPT_NAME']);
define('BASE_URI', safe_dirname(SCRIPT_URI));
define('STATIC_URI', BASE_URI.'/static');

define('CONFIG_FILE', BASE_PATH.'/config.php');

require_once(FRAME_PATH.'/main.php');

?>
