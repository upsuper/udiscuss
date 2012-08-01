<?php

/**
 * Execute database query
 *
 * This function uses printf-style string format, 
 * and it will quote all parameters passed in.
 *
 * @param PDO $db
 * @param string $sql
 * @param ... 
 * @return mixed
 */
function query($db, $sql)
{
    $args = func_get_args();
    $args = array_slice($args, 2);
    $args = array_map(array($db, 'quote'), $args);
    $sql = vsprintf($sql, $args);
    $ret = $db->query($sql);
    return $ret;
}

/**
 * Execute database query and return the first row
 *
 * @param PDO $db
 * @param string $sql
 * @param ... 
 * @return mixed
 */
function query_one($db, $sql)
{
    $result = call_user_func_array('query', func_get_args());
    if (!($result instanceof PDOStatement))
        return $result;
    $row = $result->fetch();
    return $row;
}

?>
