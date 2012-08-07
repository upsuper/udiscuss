<?php

/**
 * Manage path
 */
class PathClass
{
    private $pos = 0;
    private $path = array();

    function __construct($path_info)
    {
        $this->path = explode('/', $path_info);
        $this->pos = 0;
    }

    /**
     * Get next path entry
     *
     * @param $default string if next entry is empty, return default
     * @return string
     */
    function get_next($default = '')
    {
        $this->pos++;
        if (count($this->path) <= $this->pos)
            $next = '';
        else
            $next = $this->path[$this->pos];
        return $next;
    }

    /**
     * Get full path for given path
     * if given path begins with /, use index.php/ as root,
     * otherwise, path is relative to current path
     *
     * @param string $path
     * @param bool $include_script optional
     *          whether script path included in return value
     * @return string
     */
    function get_url($path, $include_script = true)
    {
        $ret = $this->path;
        $pos = $this->pos;
        array_splice($ret, $pos + 1);
        $path = explode('/', $path);
        if ($path[0] == '') {
            if (count($path) != 1) {
                $ret = array('');
                $pos = 0;
            }
        }
        foreach ($path as $name) {
            if ($name == '..') {
                array_splice($ret, $pos);
                --$pos;
                if ($pos <= 0)
                    $pos = 1;
            } else {
                if ($name != '' && $name != '.')
                    $ret[$pos] = $name;
                ++$pos;
            }
        }
        $ret[] = '';
        $ret = implode('/', $ret);
        if ($include_script)
            $ret = ROOT_URI.$ret;
        return $ret;
    }
}

?>
