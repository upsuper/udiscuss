<?php

// load configure file
require_once(CONFIG_FILE);

// load libraries
require_once(FRAME_PATH.'/db.func.php');
require_once(FRAME_PATH.'/mail.func.php');
require_once(FRAME_PATH.'/misc.func.php');
require_once(FRAME_PATH.'/model.class.php');
require_once(FRAME_PATH.'/request.func.php');
require_once(FRAME_PATH.'/response.func.php');
require_once(FRAME_PATH.'/controller.class.php');

// initialize database
$db = new PDO(sprintf('%s:host=%s;dbname=%s',
                      $CONFIG['db']['driver'],
                      $CONFIG['db']['hostname'],
                      $CONFIG['db']['database']),
              $CONFIG['db']['username'],
              $CONFIG['db']['password']);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_LAZY);

// clean input data
function t_stripslashes_deep($value)
{
    return is_array($value) ?
        array_map('t_stripslashes_deep', $value) : stripslashes($value);
}
if (get_magic_quotes_gpc()) {
    $_GET    = t_stripslashes_deep($_GET);
    $_POST   = t_stripslashes_deep($_POST);
    $_COOKIE = t_stripslashes_deep($_COOKIE);
}

// check post token
if (is_post()) {
    $token = get_form($CONFIG['token_field']);
    if ($token != get_token())
        exit(forbidden());
    unset($token);
}

// register model autoload
spl_autoload_register(function ($classname) {
    if (substr($classname, -11) == '_Controller')
        return;
    if (substr($classname, -5) == '_View')
        return;

    $filename = preg_replace('/(?<=[0-9a-z])[A-Z]|[A-Z](?=[a-z])/',
        '_\0', $classname);
    if ($filename[0] == '_')
        $filename = substr($filename, 1);
    $filename = strtolower($filename);
    $filename = str_replace('__', '/', $filename);
    $filename = MODEL_PATH.'/'.$filename.'.class.php';
    file_exists($filename) and include($filename);
});

// register controller autoload
// TODO

// initialize PHP settings
session_start();
mb_internal_encoding('UTF-8');
date_default_timezone_set('Asia/Shanghai');

// parse path
if (!isset($_SERVER['PATH_INFO']) || !$_SERVER['PATH_INFO'])
    $_SERVER['PATH_INFO'] = '/';
$path = explode('/', $_SERVER['PATH_INFO']);
if (end($path) != '')
    exit(redirect(implode('/', $path).'/'));
$orig_len = count($path);
$path = array_filter($path, function ($slice) {
    return $slice && $slice != '.' && $slice != '..';
});
while (end($path) == 'index')
    array_pop($path);
if (count($path) != $orig_len - 2)
    exit(redirect('/'.implode('/', $path).'/'));
unset($orig_len);

// go into controller
include(CTRL_PATH.'/__init.php');
$inst = new _Controller;
$first = $path ? array_shift($path) : '';
$resp = call_user_func_array(array($inst, $first), $path);

// return data
// TODO maybe need to process before return

?>
