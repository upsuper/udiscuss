<?php

/**
 * Get field in $_POST without notice
 *
 * @param string $name
 * @return mixed
 */
function get_form($name)
{
    return isset($_POST[$name]) ? $_POST[$name] : null;
}

/**
 * Check if method of current requrest is POST
 *
 * @return bool
 */
function is_post()
{
    return $_SERVER['REQUEST_METHOD'] == 'POST';
}

?>
