<?php

namespace jcubic\lip\LNumberTest;

use PHPUnit\Framework\TestCase;
use jcubic\lip\LNumber;
use PHP\Math\BigInteger\BigInteger;

class LNumberTest extends TestCase {
    public function testException() {
        $specs = array(false, null, array());
        foreach ($specs as $spec) {
            try {
                new LNumber($spec);
                $this->assertTrue(false);
            } catch(\Exception $e) {
                $this->assertTrue(true);
            }
        }
    }
    // -------------------------------------------------------------------------
    public function testConstructor() {
        $x = new LNumber(new LNumber(100));
        $this->assertEquals('100', (string)$x);
        $this->assertTrue(isset($x->ceil));
    }
    public function testDynamicFunctions() {
        $x = new LNumber(100.1);
        $y = $x->ceil();
        $this->assertEquals('101', (string)$y);
        $this->assertTrue($y->isBigNumber() && !$x->isBigNumber());
        $x = new LNumber(100);
        $this->assertEquals($x->ceil(), $x);
        try {
            $x->nonExisted();
            $this->assertTrue(false);
        } catch(\Exception $e) {
            $this->assertTrue(true);
        }
    }
    // -------------------------------------------------------------------------
    public function testBigInt() {
        $str = '11111111111111111111111111111111111111111111111111111111111111111111';
        $int = new LNumber('11111111111111111111111111111111111111111111111111111111111111111111');
        $this->assertEquals($str, (string)$int);
    }
    // -------------------------------------------------------------------------
    public function testFloat() {
        $specs = array(
            new LNumber(100.1),
            new LNumber('100.1', true),
            $f = new LNumber(100.1, true)
        );
        foreach ($specs as $f) {
            $this->assertEquals('100.1', (string)$f);
        }
    }
    // -------------------------------------------------------------------------
    public function testIsNumber() {
        $specs = array(
            array('1000', true),
            array('1122.x', false),
            array('1.0', true),
            array('1e2', true),
            array(100, true),
            array(1e2, true),
            array(new LNumber(100), true),
            array(new BigInteger('111234'), true)
        );
        foreach ($specs as $spec) {
            list($input, $expect) = $spec;
            $this->assertEquals($expect, LNumber::isNumber($input));
        }
    }
}
