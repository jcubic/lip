<?php

namespace jcubic\lip\TokenizerTest;

use PHPUnit\Framework\TestCase;

use jcubic\lip\Tokenizer;

class TokenizerTest extends TestCase {
    public function testTokenizerStrigns() {
        $input = '((("xxx" /()\\/xxx/g 10.1 `(foo ,(list)    ,@))))';
        $output = Tokenizer::tokenize($input);
        $this->assertEquals(
            array("(", "(", "(", '"xxx"', "/()\\/xxx/g", "10.1", "`", "(", "foo", ",", "(",
                  "list", ")", ",@", ")", ")", ")", ")"),
            $output
        );
    }
    public function testSemicolons() {
        $this->assertEquals(
            array('(', '";()"', '/;;;/g', 'baz', ')'),
            Tokenizer::tokenize('(";()" /;;;/g baz); (baz quux)'),
            'should ignore semicolons'
        );
        $this->assertEquals(
            array('(', 'foo', 'bar', 'baz', ')'),
            Tokenizer::tokenize('(foo bar baz); (baz quux)'),
            'should remove comments'
        );
    }
    public function testTokenizerWithMetaData() {
        // copy from lips tests
        $input = '(define-macro (defstruct name . fields)
  "First Line." "word" 10
  "Second Line."    "word" /regex/
  "Third Line."     "word"
  (let ((names (map (lambda (symbol) (gensym)) fields))
        (struct (gensym))
        (field-arg (gensym)))
';

        $output = array(
            array('col' => 0, 'line'=> 0, 'token' => '(', 'offset' => 0),
            array('col' => 1, 'line' => 0, 'token' => 'define-macro', 'offset' => 1),
            array('col' => 13, 'line' => 0, 'token' => ' ', 'offset' => 13),
            array('col' => 14, 'line' => 0, 'token' => '(', 'offset' => 14),
            array('col' => 15, 'line' => 0, 'token' => 'defstruct', 'offset' => 15),
            array('col' => 24, 'line' => 0, 'token' => ' ', 'offset' => 24),
            array('col' => 25, 'line' => 0, 'token' => 'name', 'offset' => 25),
            array('col' => 29, 'line' => 0, 'token' => ' ', 'offset' => 29),
            array('col' => 30, 'line' => 0, 'token' => '.', 'offset' => 30),
            array('col' => 31, 'line' => 0, 'token' => ' ', 'offset' => 31),
            array('col' => 32, 'line' => 0, 'token' => 'fields', 'offset' => 32),
            array('col' => 38, 'line' => 0, 'token' => ')', 'offset' => 38),
            array('col' => 39, 'line' => 0, 'token' => "\n", 'offset' => 39),
            array('col' => 0, 'line' => 1, 'token' => '  ', 'offset' => 40),
            array('col' => 2, 'line' => 1, 'token' => '"First Line."', 'offset' => 42),
            array('col' => 15, 'line' => 1, 'token' => ' ', 'offset' => 55),
            array('col' => 16, 'line' => 1, 'token' => '"word"', 'offset' => 56),
            array('col' => 22, 'line' => 1, 'token' => ' ', 'offset' => 62),
            array('col' => 23, 'line' => 1, 'token' => '10', 'offset' => 63),
            array('col' => 25, 'line' => 1, 'token' => "\n", 'offset' => 65),
            array('col' => 0, 'line' => 2, 'token' => '  ', 'offset' => 66),
            array('col' => 2, 'line' => 2, 'token' => '"Second Line."', 'offset' => 68),
            array('col' => 16, 'line' => 2, 'token' => '    ', 'offset' => 82),
            array('col' => 20, 'line' => 2, 'token' => '"word"', 'offset' => 86),
            array('col' => 26, 'line' => 2, 'token' => ' ', 'offset' => 92),
            array('col' => 27, 'line' => 2, 'token' => '/regex/', 'offset' => 93),
            array('col' => 34, 'line' => 2, 'token' => "\n", 'offset' => 100),
            array('col' => 0, 'line' => 3, 'token' => '  ', 'offset' => 101),
            array('col' => 2, 'line' => 3, 'token' => '"Third Line."', 'offset' => 103),
            array('col' => 15, 'line' => 3, 'token' => '     ', 'offset' => 116),
            array('col' => 20, 'line' => 3, 'token' => '"word"', 'offset' => 121),
            array('col' => 26, 'line' => 3, 'token' => "\n", 'offset' => 127),
            array('col' => 0, 'line' => 4, 'token' => '  ', 'offset' => 128),
            array('col' => 2, 'line' => 4, 'token' => '(', 'offset' => 130),
            array('col' => 3, 'line' => 4, 'token' => 'let', 'offset' => 131),
            array('col' => 6, 'line' => 4, 'token' => ' ', 'offset' => 134),
            array('col' => 7, 'line' => 4, 'token' => '(', 'offset' => 135),
            array('col' => 8, 'line' => 4, 'token' => '(', 'offset' => 136),
            array('col' => 9, 'line' => 4, 'token' => 'names', 'offset' => 137),
            array('col' => 14, 'line' => 4, 'token' => ' ', 'offset' => 142),
            array('col' => 15, 'line' => 4, 'token' => '(', 'offset' => 143),
            array('col' => 16, 'line' => 4, 'token' => 'map', 'offset' => 144),
            array('col' => 19, 'line' => 4, 'token' => ' ', 'offset' => 147),
            array('col' => 20, 'line' => 4, 'token' => '(', 'offset' => 148),
            array('col' => 21, 'line' => 4, 'token' => 'lambda', 'offset' => 149),
            array('col' => 27, 'line' => 4, 'token' => ' ', 'offset' => 155),
            array('col' => 28, 'line' => 4, 'token' => '(', 'offset' => 156),
            array('col' => 29, 'line' => 4, 'token' => 'symbol', 'offset' => 157),
            array('col' => 35, 'line' => 4, 'token' => ')', 'offset' => 163),
            array('col' => 36, 'line' => 4, 'token' => ' ', 'offset' => 164),
            array('col' => 37, 'line' => 4, 'token' => '(', 'offset' => 165),
            array('col' => 38, 'line' => 4, 'token' => 'gensym', 'offset' => 166),
            array('col' => 44, 'line' => 4, 'token' => ')', 'offset' => 172),
            array('col' => 45, 'line' => 4, 'token' => ')', 'offset' => 173),
            array('col' => 46, 'line' => 4, 'token' => ' ', 'offset' => 174),
            array('col' => 47, 'line' => 4, 'token' => 'fields', 'offset' => 175),
            array('col' => 53, 'line' => 4, 'token' => ')', 'offset' => 181),
            array('col' => 54, 'line' => 4, 'token' => ')', 'offset' => 182),
            array('col' => 55, 'line' => 4, 'token' => "\n", 'offset' => 183),
            array('col' => 0, 'line' => 5, 'token' => '        ', 'offset' => 184),
            array('col' => 8, 'line' => 5, 'token' => '(', 'offset' => 192),
            array('col' => 9, 'line' => 5, 'token' => 'struct', 'offset' => 193),
            array('col' => 15, 'line' => 5, 'token' => ' ', 'offset' => 199),
            array('col' => 16, 'line' => 5, 'token' => '(', 'offset' => 200),
            array('col' => 17, 'line' => 5, 'token' => 'gensym', 'offset' => 201),
            array('col' => 23, 'line' => 5, 'token' => ')', 'offset' => 207),
            array('col' => 24, 'line' => 5, 'token' => ')', 'offset' => 208),
            array('col' => 25, 'line' => 5, 'token' => "\n", 'offset' => 209),
            array('col' => 0, 'line' => 6, 'token' => '        ', 'offset' => 210),
            array('col' => 8, 'line' => 6, 'token' => '(', 'offset' => 218),
            array('col' => 9, 'line' => 6, 'token' => 'field-arg', 'offset' => 219),
            array('col' => 18, 'line' => 6, 'token' => ' ', 'offset' => 228),
            array('col' => 19, 'line' => 6, 'token' => '(', 'offset' => 229),
            array('col' => 20, 'line' => 6, 'token' => 'gensym', 'offset' => 230),
            array('col' => 26, 'line' => 6, 'token' => ')', 'offset' => 236),
            array('col' => 27, 'line' => 6, 'token' => ')', 'offset' => 237),
            array('col' => 28, 'line' => 6, 'token' => ')', 'offset' => 238),
            array('col' => 29, 'line' => 6, 'token' => "\n", 'offset' => 239)
        );
        $this->assertEquals(
            $output,
            Tokenizer::tokenize($input, true)
        );
    }
}
