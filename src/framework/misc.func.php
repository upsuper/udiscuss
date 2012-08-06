<?php

/**
 * Check if a string starts with the given pattern
 *
 * @param string $haystack String to check
 * @param string $needle Patten
 * @return bool
 */
function start_with($haystack, $needle)
{
    $length = strlen($needle);
    return substr($haystack, 0, $length) === $needle;
}

?>
