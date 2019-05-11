<?php

namespace jcubic\lip;

use jcubic\lip\Symbol;
use function jcubic\lip\lastItem;

class Tokenizer {
    static $specials = array();
    static $re_re = "%^\/((?:\\\/|[^/]|\[[^\]]*\/[^\]]*\])+)\/([gimy]*)$%";
    static $int_re = "%^[-+]?[0-9]+([eE][-+]?[0-9]+)?$%";
    static $float_re = "%^([-+]?((\.[0-9]+|[0-9]+\.[0-9]+)([eE][-+]?[0-9]+)?)|[0-9]+\.)$%";

    static $pre_parse_re = "%(\"(?:\\\\[\\\\S\\\\s]|[^\"])*\"|\/(?! )[^\/\\\\]*(?:\\\\[\S\s][^\/\\\\]*)*\/[gimy]*(?=\s|\(|\)|$)|;.*)%";
    static $string_re = '%"(?:\\\\[\\S\\s]|[^"])*"%';

    // -------------------------------------------------------------------------
    static function makeTokenRe() {
        $tokens = implode("|", array_map('preg_quote', array_keys(self::$specials)));
        return '%("(?:\\\\[\\S\\s]|[^"])*"|\\/(?! )[^\\/\\\\]*(?:\\\\[\\S\\s][^\\/\\\\]*)*\\/[gimy]*(?=\\s|\\(|\\)|$)|\\(|\\)|\'|"(?:\\\\[\\S\\s]|[^"])+|\\n|(?:\\\\[\\S\\s]|[^"])*"|;.*|(?:[-+]?(?:(?:\\.[0-9]+|[0-9]+\\.[0-9]+)(?:[eE][-+]?[0-9]+)?)|[0-9]+\\.)[0-9]|\\.{2,}|' . $tokens . '|[^(\\s)]+)%';
    }

    // -------------------------------------------------------------------------
    static function tokens($str) {
        $tokens_re = self::makeTokenRe();
        $str = preg_replace("/\n\r|\r/", "\n", $str);
        $count = 0;
        $line = 0;
        $tokens = array();
        $current_line = array();
        $col = 0;
        $parts = preg_split(self::$pre_parse_re, $str, -1,
                            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        foreach ($parts as $string) {
            if (preg_match(self::$pre_parse_re, $string)) {
                $col = 0;
                if (count($current_line) > 0) {
                    $lastToken = lastItem($current_line);
                    if (preg_match("/\n/", $lastToken['token'])) {
                        $lines = explode("\n", $lastToken['token']);
                        $last_line = array_pop($lines);
                        $col += strlen($last_line);
                    } else {
                        $col += strlen($lastToken['token']);
                    }
                    $col += $lastToken['col'];
                }
                $token = array(
                    'col' => $col,
                    'line' => $line,
                    'token' => $string,
                    'offset' => $count
                );
                $tokens[] = $token;
                $current_line[] = $token;
                $count += strlen($string);
                $col += strlen($string);
                if (preg_match_all("/\n/", $string, $m, PREG_PATTERN_ORDER)) {
                    $line += count($m[0]);
                }
            } else {
                $strings = preg_split($tokens_re, $string, -1,
                                      PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
                foreach ($strings as $string) {
                    $token = array(
                        'col' => $col,
                        'line' => $line,
                        'token' => $string,
                        'offset' => $count
                    );
                    $col += strlen($string);
                    $count += strlen($string);
                    $tokens[] = $token;
                    $current_line[] = $token;
                    if ($string == "\n") {
                        ++$line;
                        $current_line = array();
                        $col = 0;
                    }
                }
            }
        }
        return $tokens;
    }
    // -------------------------------------------------------------------------
    static function tokenize($string, $extra = false) {
        $tokens = Tokenizer::tokens($string);
        if ($extra) {
            return $tokens;
        } else {
            $simple = array_map(function($token) {
                return trim($token['token']);
            }, $tokens);
            return array_values(array_filter($simple, function($token) {
                return $token != '' && !preg_match("/^;/", $token);
            }));
        }
    }
    static function init() {
        self::$specials = array(
            "'" => new Symbol('quote'),
            '`' => new Symbol('quasiquote'),
            ',@' => new Symbol('unquote-splicing'),
            ',' => new Symbol('unquote')
        );
    }
}

Tokenizer::init();

?>
