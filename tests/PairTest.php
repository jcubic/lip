<?php

namespace jcubic\lip\PairTest;

use PHPUnit\Framework\TestCase;

use jcubic\lip\Pair;

class PairTest extends TestCase {
    static $assoc = '(("x" . 10) ("y" . 20) ("z" . 30))';
    static $list = '(("x" 10) ("y" 20) ("z" 30))';
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
    public function testCreateArrayOfArrays() {

        $output = (string)Pair::fromArray(array(
            array('x', 10),
            array('y', 20),
            array('z', 30)
        ));

        $this->assertEquals(PairTest::$list, $output);
    }

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
}

?>
