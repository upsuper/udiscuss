<?php

// load configure file
require_once(CONFIG_FILE);

// load libraries
require_once(FRAME_PATH.'/db.func.php');
require_once(FRAME_PATH.'/mail.func.php');
require_once(FRAME_PATH.'/path.class.php');
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

// register model autoload
function model_autoload($classname)
{
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
}
spl_autoload_register('model_autoload');

// register controller autoload
// TODO

// initialize PHP settings
session_start();
mb_internal_encoding('UTF-8');
date_default_timezone_set('Asia/Shanghai');

// parse path
if (! isset($_SERVER['PATH_INFO']) || ! $_SERVER['PATH_INFO'])
    $_SERVER['PATH_INFO'] = '/';
$path = explode('/', $_SERVER['PATH_INFO']);
if (end($path) != '')
    redirect(implode('/', $path).'/');
$orig_len = count($path);
function filter_path($var)
{
    return $var && $var != '.' && $var != '..';
}
$path = array_filter($path, 'filter_path');
while (end($path) == 'index')
    array_pop($path);
if (count($path) != $orig_len - 2)
    redirect('/'.implode('/', $path).'/');

// go into controller
include(CTRL_PATH.'/__init.php');
$inst = new _Controller;
$first = array_shift($path);
$resp = $inst->__call($first, $path);

// return data
// TODO maybe need to process before return

?>
