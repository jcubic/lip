<?php

namespace jcubic\lip;

class Gensym {
    private static $count = 0;
    private $name;
    function __construct($name = 'g_') {
        global $gensym;
        $this->name = "#" . $name . (++self::$count) . "#";
    }
    function __toString() {
        return $this->name;
    }
}

class Symbol {
    public $name;
    function __construct($name) {
        $this->name = $name;
    }
    function __toString() {
        return (string)$this->name;
    }
}

?>
