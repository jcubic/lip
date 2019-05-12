<?php

namespace jcubic\lip\PairTest;

use PHPUnit\Framework\TestCase;

use jcubic\lip\Pair;
use jcubic\lip\Symbol;

class PairTest extends TestCase {
    static $assoc = '(("x" . 10) ("y" . 20) ("z" . 30))';
    static $list = '(("x" 10) ("y" 20) ("z" 30))';
    static $symbols = '((x 10) (y 20) (z 30))';
    // -------------------------------------------------------------------------
    public function testCreatePair() {
        $x = new Pair('1');
        $this->assertEquals(Pair::$nil, $x->cdr);
    }
    // -------------------------------------------------------------------------
    public function testAppend() {
        $x = Pair::fromArray(array(1, 2, 3, 4));
        $x->append(10);
        $this->assertEquals('(1 2 3 4 10)', (string)$x);
        $x->append(new Pair(10, 20));
        $this->assertEquals('(1 2 3 4 10 10 . 20)', (string)$x);
    }
    // -------------------------------------------------------------------------
    public function testMagicMethods() {
        $x = new Pair();
        $x->car = 10;
        $x->cdr = Pair::$nil;
        try {
            $y = $x->xxx;
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }
    // -------------------------------------------------------------------------
    public function testCreateAssocFromArray() {
        $output = (string)Pair::fromArray(array_map(function($pair) {
            list($car, $cdr) = $pair;
            return new Pair($car, $cdr);
        }, array(
            array('x', 10),
            array('y', 20),
            array('z', 30)
        )));

        $this->assertEquals(PairTest::$assoc, $output);
    }
    // -------------------------------------------------------------------------
    public function testCreateArrayOfArrays() {

        $output = (string)Pair::fromArray(array(
            array('x', 10),
            array('y', 20),
            array('z', 30)
        ));

        $this->assertEquals(PairTest::$list, $output);
    }
    // -------------------------------------------------------------------------
    public function testFromArray() {
        $output = Pair::fromArray(array());
        $this->assertEquals(Pair::emptyList(), $output);
        $output = Pair::fromArray(new Pair());
        $this->assertEquals(Pair::emptyList(), $output);
        $output = Pair::fromArray(100);
        $this->assertEquals(null, $output);
    }
    // -------------------------------------------------------------------------
    public function testEmptyList() {
        $specs = array(
            array(100, false),
            array(new Pair(), true),
            array(Pair::emptyList(), true),
            array("foo", false),
            array(array(), false),
            array(false, false)
        );
        foreach ($specs as $spec) {
            list($obj, $expected) = $spec;
            $this->assertEquals($expected, Pair::isEmptyList($obj));
        }
    }
    // -------------------------------------------------------------------------
    public function testCreateAssocArrayFromObject() {
        $input = new \stdClass();
        $input->x = 10;
        $input->y = 20;
        $input->z = 30;
        $output = Pair::fromObject($input);

        echo (string)$output;

        $this->assertTrue($output instanceof Pair);

        $array = array_map(function($item) {
            if (is_array($item)) {
                return (array)$item;
            }
            return $item;
        }, (array)$input);

        $this->assertEquals($array, $output->toObject());
    }
    // -------------------------------------------------------------------------
    public function testCreateAssocArrayFromArray() {
        $input = array(
            'x' => 10,
            'y' =>  20,
            'z' =>  30
        );
        $output = Pair::fromObject($input);

        $this->assertTrue($output instanceof Pair);

        $this->assertEquals($input, $output->toObject());
    }
    // -------------------------------------------------------------------------
    public function testSymbols() {
        $input = array(
            array(new Symbol('x'), 10),
            array(new Symbol('y'), 20),
            array(new Symbol('z'), 30)
        );
        $output = (string)Pair::fromArray($input);

        $this->assertEquals(PairTest::$symbols, $output);

        $output = Pair::fromPairs($input);
        $this->assertEquals(array(
            'x' => 10,
            'y' => 20,
            'z' => 30
        ), $output->toObject());
    }
    // -------------------------------------------------------------------------
    public function testToArray() {
        $inputs = array(
            array(),
            array(1, 2, 3, 4, 5),
            array(array(1), array(2, array(3)), 4)
        );
        foreach ($inputs as $input) {
            $list = Pair::fromArray($input);
            $this->assertEquals($input, $list->toArray());
        }
    }
}

?>
