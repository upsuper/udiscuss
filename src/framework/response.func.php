<?php

/**
 * Send status header
 *
 * @param string $header
 */
function status_header($header)
{
    header($_SERVER['SERVER_PROTOCOL'].' '.$header);
    header('Status: '.$header);
}

/**
 * Get absolute path related to root of website
 *
 * @param string $path
 * @return string
 */
function abs_url($path)
{
    return ROOT_URI.$path;
}

/**
 * Redirect to given path
 * if path begins with / then use index.php/ as root,
 * otherwise, redirect directly.
 * this function will NEVER return.
 * 
 * @param string $to_path optional, path to redirect
 * @param bool $permanent optional, if true, send 301
 */
function redirect($to_path = '.', $permanent = false)
{
    global $PATH;
    if ($to_path[0] == '/')
        $to_path = abs_url($to_path);
    if ($permanent)
        status_header('301 Moved Permanently');
    header('Location: '.$to_path);
    exit();
}

/**
 * Return 404 Not Found
 * this function will NEVER return.
 */
function not_found()
{
    status_header('404 Not Found');
    echo '<h1>Not Found</h1>';
    exit();
}

/**
 * Return 403 Forbidden
 * this function will NEVER return.
 */
function forbidden()
{
    status_header('403 Forbidden');
    echo '<h1>Forbidden</h1>';
    exit();
}

/**
 * Add flash message
 *
 * @param string $msg
 * @param string $type type can be:
 *              success, fail, error, info, warn
 */
function flash($msg, $type)
{
    $_SESSION['flash'][] = array($msg, $type);
}

/**
 * Get a token for submitting for CRSF
 * call this function will always return a same value for a session
 *
 * @return string
 */
function get_token()
{
    if (! isset($_SESSION['token'])) {
        $token = pack('LLL', mt_rand(), mt_rand(), mt_rand());
        $_SESSION['token'] = strtr(base64_encode($token), '+/', '_-');
    }
    return $_SESSION['token'];
}

/**
 * Apply template
 *
 * @param string $tpl template name
 * @param array $data optional, template data
 */
function template($tpl, $data = array())
{
    
    spl_autoload_register(function ($classname) {
        if (start_with($classname, Template::VIEW_PREFIX)) {
            include(CACHE_PATH.Template::VIEW_PATH.'/'.
                substr($classname, strlen(Template::VIEW_PREFIX)).'.php');
        }
    });

    require_once(FRAME_PATH.'/view.class.php');
    require_once(FRAME_PATH.'/template.class.php');
    $view = Template::compile($tpl);
    $inst = new $view($data);
    echo $inst->_render();
}

/**
 * Output JSON data
 *
 * @param mixed $data
 */
function json($data)
{
    echo json_encode($data);
}

?>
