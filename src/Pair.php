<?php

namespace jcubic\lip;

//use function jcubic\lip\toString;

define('undefined', '___UNDEFINED___');

class Pair {
    static $nil = null;
    function __construct($car = '___UNDEFINED___', $cdr = '___UNDEFINED___') {
        $this->car = $car;
        if ($cdr == '___UNDEFINED___') {
            $this->cdr = self::$nil;
        } else {
            $this->cdr = $cdr;
        }
    }
    // -----------------------------------------------------------------------------------
    function set($prop, $value) {
        if (in_array($prop, array('car', 'cdr'))) {
            $this->$prop = $value;
            if ($value instanceof Pair) {
                // TOOD: mark cycles
            }
        }
    }
    // -----------------------------------------------------------------------------------
    function append($pair) {
        if (is_array($pair)) {
            return $this->append(Pair::fromArray($pair));
        }
        $p = $this;
        if ($p->car == undefined) {
            if ($par instanceof Pair) {
                $this->car = $pair->car;
                $this->cdr = $pair->cdr;
            } else {
                $this->car = $pair;
            }
        } else {
            while (true) {
                if ($p instanceof Pair && $p->cdr != self::$nil) {
                    $p = $p->cdr;
                } else {
                    break;
                }
            }
            if ($pair instanceof Pair) {
                $p->cdr = $pair;
            } else if ($pair != self::$nil) {
                $p->cdr = new Pair($pair, self::$nil);
            }
        }
        return $this;
    }
    // -----------------------------------------------------------------------------------
    function haveCycles($name = null) {
        if ($name == null) {
            return $this->haveCycles('car') || $this->haveCycles('cdr');
        }
        return isset($this->cycles) && isset($this->cycles->$name);
    }
    // -----------------------------------------------------------------------------------
    function map($fn) {
        if ($this->car != undefined) {
            return new Pair(
                $fn($this->car),
                self::isEmptyList($this->cdr) ? self::$nil : $this->cdr->map($fn)
            );
        }
    }
    // -----------------------------------------------------------------------------------
    function reduce($fn, $init = '___UNDEFINED___') {
        $node = $this;
        $result = $init == undefined ? self::$nil : $init;
        while (true) {
            if ($node != self::$nil) {
                $result = $fn($result, $node->car);
                $node = $node->cdr;
            } else {
                break;
            }
        }
        return $result;
    }
    // -----------------------------------------------------------------------------------
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
                    $rest = $this->cdr->__toString();
                    $cdr = preg_replace("/^\(|\)$/", "", $rest);
                    $arr[] = ' ';
                    $arr[] = $cdr;
                }
            } else if (!self::isNil($this->cdr)) {
                if (is_string($this->cdr)) {
                    $arr = array_merge($arr, array(' . ', json_encode($this->cdr)));
                } else {
                    $arr = array_merge($arr, array(' . ', toString($this->cdr)));
                }
            }
        }
        $arr[] = ')';
        return implode("", $arr);
    }
    // -----------------------------------------------------------------------------------
    function toArray() {
        if (self::isEmptyList($this)) {
            return array();
        }
        $result = array();
        if ($this->car instanceof Pair) {
            $result[] = $this->car->toArray();
        } else {
            $result[] = $this->car;
        }
        if ($this->cdr instanceof Pair) {
            $result = array_merge($result, $this->cdr->toArray());
        }
        return $result;
    }
    // -----------------------------------------------------------------------------------
    function toObject() {
        $node = $this;
        $result = array();
        while (true) {
            if ($node instanceof Pair && $node->car instanceof Pair) {
                $pair = $node->car;
                $name = $pair->car;
                if ($name instanceof Symbol) {
                    $name = $name->name;
                }
                $cdr = $pair->cdr;
                if ($cdr instanceof Pair) {
                    $cdr = $cdr->toObject();
                }
                //else if ($cdr instanceof LNumber) {
                //  $cdr = $cdr->valueOf();
                //}
                $result[$name] = $cdr;
                $node = $node->cdr;
            } else {
                break;
            }
        }
        return $result;
    }
    // -----------------------------------------------------------------------------------
    static function fromPairs($array) {
        return array_reduce($array, function($list, $pair) {
            // TODO: \lip\Symbol\Symbol
            list($key, $value) = $pair;
            return new Pair(
                new Pair($key, $value),
                $list
            );
        }, self::$nil);
    }
    // -----------------------------------------------------------------------------------
    static private function toPair($obj, $keys, $fn) {
        return Pair::fromPairs(array_map(function($key) use ($obj, $fn) {
            return array($key, $fn($key));
        }, $keys));
    }
    // -----------------------------------------------------------------------------------
    static function fromObject($obj) {
        if (is_array($obj)) {
            return Pair::toPair($obj, array_keys($obj), function($key) use ($obj) {
                return $obj[$key];
            });
            return Pair::fromPairs(array_map(function($key) use ($obj) {
                return array($key, $obj[$key]);
            }, array_keys($obj)));
        } else if (is_object($obj)) {
            return Pair::toPair($obj, get_object_vars($obj), function($key) use ($obj) {
                return $obj->$key;
            });
            return Pair::fromPairs(array_map(function($key) use ($obj) {
                return array($key, $obj->$key);
            }, get_object_vars($obj)));
        }
    }
    // -----------------------------------------------------------------------------------
    static function fromArray($array) {
        if ($array instanceof Pair) {
            return $array;
        }
        if (is_array($array)) {
            $len = count($array);
            if ($len == 0) {
                return emptyList();
            }
            if (is_array($array[0])) {
                $car = Pair::fromArray($array[0]);
            } else {
                $car = $array[0];
            }
            if ($len == 1) {
                return new Pair($car, self::$nil);
            } else {
                return new Pair($car, Pair::fromArray(array_slice($array, 1)));
            }
        }
    }
    // -------------------------------------------------------------------------
    static function isEmptyList($x) {
        return $x instanceof Pair && $x->car == undefined && $x->cdr == self::$nil;
    }
    // -------------------------------------------------------------------------
    static function init() {
        self::$nil = new Nil();
    }
    // -------------------------------------------------------------------------
    static function isNil($value) {
        return $value instanceof Nil && $value == self::$nil;
    }
    // -------------------------------------------------------------------------
    static function emptyList() {
        return new Pair();
    }
}

class Nil {
    function __toString() {
        return 'nil';
    }
}

Pair::init();


?>
