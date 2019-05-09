<?php

namespace lip\utils;

require_once('Pair.php');

function toString($value) {
    if (is_callable($value)) {
        $ref = new \ReflectionFunction($value);
        $name = $ref->getName();
        return "<#function$name>";
    } elseif (is_string($value)) {
        return json_encode($value);
    } elseif ($value instanceof \lip\Pair\Pair ||
               $value instanceof \lip\Symbol\Symbol ||
               $value instanceof \lip\LNumber\Lnumber ||
               $value == \lip\Pair\nil()) {
        return (string)$value;
    } elseif (is_array($value)) {
        return array_map('\lip\utils\toString', $value);
    } elseif (is_object($value)) {
        $name = get_class($value);
        return "<#object(${name})>";
    } else if (isset($value)) {
        return $value;
    }
}

?>
