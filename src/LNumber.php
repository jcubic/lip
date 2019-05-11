<?php

namespace jcubic\lip;

use PHP\Math\BigInteger\BigInteger;

class LNumber {
    private $value;
    private $float;
    // -----------------------------------------------------------------------------------
    public function __construct($value, $float = false) {
        $this->float = false;
        if ($value instanceof LNumber) {
            $this->value = $value->value;
            $this->float = $value->float;
        } else {
            if (!LNumber::isNumber($value)) {
                // TODO: type function
                $type = gettype($value);
                throw new \Exception("You can't create LNumber from $type");
            }
            if ($float) {
                if (is_string($value)) {
                    $this->value = floatval($value);
                } else {
                    $this->value = $value;
                }
                $this->float = true;
            } else if (is_float($value)) {
                $this->value = $value;
            } else {
                $this->value = new BigInteger($value);
            }
        }
    }
    // -----------------------------------------------------------------------------------
    function __toString() {
        if ($this->value instanceof BigInteger) {
            return $this->value->getValue();
        } else {
            return (string)$this->value;
        }
    }
    // -----------------------------------------------------------------------------------
    static function isNumber($n) {
        if ($n instanceof LNumber) {
            return LNumber::isNumber($n->value);
        }
        return is_numeric($n) || is_float($n) || is_int($n) || $n instanceof BigInteger;
    }
}




