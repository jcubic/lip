<?php

namespace lip\Tokenizer;

$re_re = "%^\/((?:\\\/|[^/]|\[[^\]]*\/[^\]]*\])+)\/([gimy]*)$%";
$int_re = "%^[-+]?[0-9]+([eE][-+]?[0-9]+)?$%";
$float_re = "%^([-+]?((\.[0-9]+|[0-9]+\.[0-9]+)([eE][-+]?[0-9]+)?)|[0-9]+\.)$%";

$pre_parse_re = '%("(?:\\[\S\s]|[^"])*"|\/(?! )[^\/\\]*(?:\\[\S\s][^\/\\]*)*\/[gimy]*(?=\s|\(|\)|$)|;.*)%';
$string_re = '%"(?:\\[\S\s]|[^"])*"%';

function escapeRegex($str) {
    if (is_string($str)) {
        $special = '%([-\\^$[\]()+{}?*.|])%';
        return preg_replace($special, '\\$1', $str);
    }
}

function makeTokenRe() {
    global $specials;
    $tokens = implode("|", array_map('\lip\Tokenizer\escapeRegex', array_keys($specials)));
    return '%("(?:\\\\[\\S\\s]|[^"])*"|\\/(?! )[^\\/\\\\]*(?:\\\\[\\S\\s][^\\/\\\\]*)*\\/[gimy]*(?=\\s|\\(|\\)|$)|\\(|\\)|\'|"(?:\\\\[\\S\\s]|[^"])+|\\n|(?:\\\\[\\S\\s]|[^"])*"|;.*|(?:[-+]?(?:(?:\\.[0-9]+|[0-9]+\\.[0-9]+)(?:[eE][-+]?[0-9]+)?)|[0-9]+\\.)[0-9]|\\.{2,}|' . $tokens . '|[^(\\s)]+)%';
}
function lastItem($array, $n = 1) {
    return $array[count($array) - n];
}

function tokens($str) {
    global $pre_parse_re;
    $tokens_re = makeTokenRe();
    $str = preg_replace("/\n\r|\r/", "\n", $str);
    $count = 0;
    $line = 0;
    $tokens = array();
    $current_line = array();
    $col = 0;
    $parts = preg_split($pre_parse_re, $str, -1, PREG_SPLIT_DELIM_CAPTURE);
    foreach (array_filter($parts) as $string) {
        if (preg_match($pre_parse_re, $string)) {
            $col = 0;
            if (count($current_line) > 0) {
                $lastToken = lastItem($current_line);
                if (preg_match("/\n/", $lastToken)) {
                    $lines = explode("\n", $lastToken);
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
            $count += srelen($string);
            $col += strlen($string);
            if (preg_match_all("/\n/g", $string, $m, PREG_PATTERN_ORDER)) {
                $line = count($m[0]);
            } else {
                $line = 0;
            }
        } else {
            $strings = preg_split($tokens_re, $str, -1, PREG_SPLIT_DELIM_CAPTURE);
            foreach (array_filter($strings) as $string) {
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
                if ($string === '\n') {
                    ++$line;
                    $current_line = array();
                    $col = 0;
                }
            }
        }
    }
    return $tokens;
}
$specials = array(
    "'" => 'quote',//new Symbol('quote'),
    '`' => 'quasiquote', //new Symbol('quasiquote'),
    ',@' => 'unquote-splicing',//new Symbol('unquote-splicing'),
    ',' => 'unquote'//new Symbol('unquote')
);

function tokenize($string, $extra = false) {
    $tokens = \lip\Tokenizer\tokens($string);
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

?>
