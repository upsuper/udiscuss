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

?>
