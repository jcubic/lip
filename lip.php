<?php

namespace lip;

require_once('utils.php');
require_once('tokenizer.php');
require_once('Pair.php');

function tokenize($string, $extra = false) {
    return \lip\Tokenizer\tokenize($string, $extra);
}

?>
