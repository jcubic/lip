<?php

namespace lip\Pair;

require_once('utils.php');

use function \lip\utils\toString;

define('undefined', '___UNDEFINED___');

class Pair {
    function __construct($car = '___UNDEFINED___', $cdr = '___UNDEFINED___') {
        $this->car = $car;
        if ($cdr == '___UNDEFINED___') {
            $this->cdr = nil();
        } else {
            $this->cdr = $cdr;
        }
    }
    function __toString() {
        $arr = array('(');
        if ($this->car != undefined) {
            if (isset($this->cycles) && isset($this->cylces->car)) {
                $value = $this->cycles->car;
            } else {
                $value = toString($this->car);
            }
            if (isset($value)) {
                $arr[] = $value;
            }
            if ($this->cdr instanceof Pair) {
                if (isset($this->cycles) && isset($this->cyles->cdr)) {
                    $arr[] = ' . ';
                    $arr[] = $this->cycles->cdr;
                } else {
                    if (isset($this->cycles) && isset($this->cycles->cdr)) {
                        $name = $this->cycles->cdr;
                    }
                    $cdr = preg_replace("/^\(|\)$/", "", $this->cdr->__toString());
                    $arr[] = ' ';
                    $arr[] = $cdr;
                }
            } else if ($this->cdr != nil()) {
                if (is_string($this->cdr)) {
                    $arr = array_merge(array(' . ', json_encode($this->cdr)));
                } else {
                    $arr = array_merge(array(' . ', toString($this->cdr)));
                }
            }
        }
        $arr[] = ')';
        return implode("", $arr);
    }
}

function isEmptyList($x) {
    return $x instanceof Pair && $x->car == undefined && $x->cdr == nil();
}


class Nil {
    function __toString() {
        return 'nil';
    }
}

$nil = new Nil();

function nil() {
    global $nil;
    return $nil;
}

function undefined() {}

function emptyList() {
    return new Pair();
}

?>
