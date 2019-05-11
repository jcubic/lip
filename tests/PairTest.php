<?php

use PHPUnit\Framework\TestCase;

use lip\Pair\Pair;

class PairTest extends TestCase {
    static $assoc = '(("x" . 10) ("y" . 20) ("z" . 30))';
    public function testCreateAssocFromArray() {

        $output = (string)Pair::fromArray(array_map(function($pair) {
            list($car, $cdr) = $pair;
            return new Pair($car, $cdr);
        }, array(
            array('x', 10),
            array('y', 20),
            array('z', 30)
        )));

        $this->assertEquals($output, PairTest::$assoc);
    }
}

?>
