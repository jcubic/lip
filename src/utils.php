<?php

namespace jcubic\lip;

use jcubic\lip\Pair;
use jcubic\lip\Symbol;
use jcubic\lip\LNumber;

// ---------------------------------------------------------------------------------------
function toString($value) {
    if ($value instanceof \ReflectionFunction) {
        $name = $value->getName();
        return "<#function$name>";
    } elseif ($value instanceof \ReflectionClass) {
        $name = $value->getName();
        return "<#class($name)>";
    } elseif (is_string($value)) {
        return json_encode($value);
    } elseif ($value instanceof Pair ||
               $value instanceof Symbol ||
               $value instanceof Lnumber ||
               Pair::isNil($value)) {
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

// ---------------------------------------------------------------------------------------
// :: Function return global reference as value or as Reflection object
// ---------------------------------------------------------------------------------------
function getGlobalVar($name) {
    if (class_exists($name)) {
        return new \ReflectionClass($name);
    } elseif (function_exists($name)) {
        return new \ReflectionFunction($name);
    } else {
        foreach($GLOBALS as $var_name => $value) {
            if ($name == $var_name) {
                return $value;
            }
        }
    }
}

// ---------------------------------------------------------------------------------------
function lastItem($array, $n = 1) {
    return $array[count($array) - $n];
}
