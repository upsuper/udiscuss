<?php

abstract class Model
{
    public function __get($name)
    {
        $get_name = '_get_'.$name;
        if (method_exists($this, $get_name))
            return call_user_func(array($this, $get_name));

        $set_name = '_set_'.$name;
        if (method_exists($this, $set_name))
            throw new Exception("Property $name is write only");
        throw new Exception("Property $name not found");
    }

    public function __isset($name)
    {
        $get_name = '_get_'.$name;
        $set_name = '_set_'.$name;
        return method_exists($this, $get_name) ||
            method_exists($this, $set_name);
    }

    public function __set($name, $value)
    {
        $set_name = '_set_'.$name;
        if (method_exists($this, $get_name))
            if (!call_user_func(array($this, $set_name), $value))
                throw new Exception("Set property $name failed");

        $get_name = '_get_'.$name;
        if (method_exists($this, $get_name))
            throw new Exception("Property $name is read only");
        throw new Exception("Property $name not found");
    }
}

?>
