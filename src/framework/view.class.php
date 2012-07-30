<?php

abstract class View
{
    private $data = array();

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        } else {
            return null;
        }
    }

    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    public function render()
    {
        ob_start();
        $this->page();
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    public abstract function page();
}

?>
