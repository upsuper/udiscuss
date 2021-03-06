<?php

class Controller
{
    public function __construct()
    {
    }

    public function __call($name, $args)
    {
        if (!$name)
            return call_user_func_array(array($this, 'index'), $args);
        if (!$args)
            $args = array('index');
        
        $ref = new ReflectionClass($this);
        $filename = $ref->getFileName();
        if (basename($filename) != '__init.php')
            return not_found();
        $classname = $ref->getName();
        $dir = dirname($filename);
        $nextclass = substr($classname, 0, -11).
            ucfirst(strtolower($name)).'_Controller';

        $next_files = array(
            $dir.'/'.$name.'/__init.php',
            $dir.'/'.$name.'.php'
        );
        foreach ($next_files as $file) {
            if (file_exists($file)) {
                include($file);
                break;
            }
        }
        if (!class_exists($nextclass, false))
            return not_found();

        $inst = new $nextclass($args);
        $action = array_shift($args);
        return call_user_func_array(array($inst, $action), $args);
    }
}

?>
