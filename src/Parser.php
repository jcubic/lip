<?php

namespace jcubic\lip;

use jcubic\lip\Tokenizer;
use jcubic\lip\Symbol;
use jcubic\lip\Pair;

class Parser {
    private function pop_join() {
        $len = count($this->stack);
        $top = $this->stack[$len - 1];
        if (is_array($top) && $top[0] instanceof Symbol &&
            in_array($top[0]->name, $this->special_forms) &&
            $len > 1 && !$top[0]->literal) {
            array_pop($this->stack);
            $len = count($this->stack);
            $last = $stack[$len - 1];
            if (is_array($last) && count($last) > 1 &&
                $last[0] instanceof Symbol) {
                $last[] = $top;
            } else if ($len == 0) {
                $this->stack[$len - 1] = $top;
            } else if ($last instanceof Pair) {
                if ($last->cd instanceof Pair) {
                    $this->stack[$len - 1] = new Pair(
                        $last,
                        Pair::fromArray($top)
                    );
                } else {
                    $this->stack[$len - 1]->cdr = Pair::fromArray($top);
                }
            } else {
                $last[] = $top;
            }
        }
    }
    // -------------------------------------------------------------------------
    private function reset() {
        $this->special_tokens = array_keys(Tokenizer::$specials);
        $this->stack = array();
        $this->special = null;
        $this->special_forms = array_values(Tokenizer::$specials);
        $this->parents = 0;
        $this->first_value = false;
        $this->specials_stack = array();
        $this->single_list_specials = array();
        $this->special_count = 0;
    }
    // -------------------------------------------------------------------------
    private parse_value($value) {
        /* TODO: write code from parse_argument */
        return $value;
    }
    // -------------------------------------------------------------------------
    public function parse($tokens) {
        if (is_string($tokens)) {
            throw new Exception("parse require tokenized array of tokens not string");
        }
        $result = array();
        $this->reset();
        foreach ($tokens as $token) {
            $len = count($this->stack);
            $top = $len == 0 ? null : $this->stack[$len - 1];
            if (in_array($token, $this->special_tokens)) {
                $this->special_count++;
                $this->special = $token;
                $this->stack[] = array($self::specials[$this->special]);
                if (!$this->special) {
                    $this->single_list_specials = array();
                }
                $this->single_list_specials[] = $this->special;
            } else {
                if ($this->special != null) {
                    $this->specials_stack[] = $this->single_list_specials;
                    $this->single_list_specials = array();
                }
                if ($token == '(') {
                    $this->first_value = true;
                    $this->parents++;
                    $this->stack[] = array();
                    $this->special = null;
                    $this->special_count = 0;
                } else if ($token == '.' && !$this->first_value) {
                    $this->stack[count($this->stack) - 1] = Pair::fromArray($top);
                } else if ($token == ')') {
                    $this->parents--;
                    if ($len == 0) {
                        throw new Exception("Unbalanced parenthesis");
                    }
                    if ($len == 1) {
                        $result[] = array_pop($this->stack);
                    } else if ($len > 1) {
                        $list = array_pop($this->stack);
                        $len = count($this->stack);
                        $top = $this->stack[$len - 1];
                        if (is_array($top)) {
                            $this->stack[$len - 1][] = $list;
                        } else if ($top instanceof Pair) {
                            $top->append(Pair::fromArray($list));
                        }
                        if (count($this->specials_stack) > 0) {
                            $this->single_list_special = array_pop($this->specials_stack);
                            while (count($this->single_list_special) > 0) {
                                $this->pop_join();
                                array_pop($this->single_list_special);
                            }
                        } else {
                            $this->pop_join();
                        }
                    }
                    if ($this->parents == 0 && count($this->stack) > 0) {
                        $result[] = array_pop($this->stack);
                    }
                } else {
                    $this->first_value = false;
                    $value = $this->parse_value($token);
                    $len = count($this->stack);
                    if ($this->special && $this->special_count > 0) {
                        while ($this->special_count--) {
                            $this->stack[$len][] = $value;
                            $value = array_pop($this->stack);
                        }
                        array_pop($this->specials_stack);
                        $this->special_count = 0;
                        $this->special = false;
                    } else if ($value instanceof Symbol &&
                               in_array($value->name, $this->special_tokens)) {
                        $value->literal = true;
                    }
                    $len = count($this->stack);
                    $top = $this->stack[$len - 1];
                    if ($top instanceof Pair) {
                        $node = $top;
                        while (true) {
                            if (Pair::isNil($node->cdr)) {
                                if (is_array($value)) {
                                    $node->cdr = Pair::fromArray($value);
                                } else {
                                    $node->cdr = $value;
                                }
                                break;
                            } else {
                                $node = $node->cdr;
                            }
                        }
                    } else if ($len == 0) {
                        $result[] = $value;
                    } else {
                        $this->stack[$len - 1][] = $value;
                    }
                }
            }
        }
        $parntesis = array_filter($tokens, function($t) {
            return preg_match("/^[()]$/", $t);
        });
        if (count($parntesis) == 0 && count($this->stack) > 0) {
            $result = array_merge($result, $stack);
            $this->stack = [];
        }
        if (count($this->stack) > 0) {
            throw new Exception("Unbalanced parenthesis 2");
        }
        return array_map(function($arg) {
            if (is_array($arg)) {
                return Pair::fromArray($arg);
            }
            return $arg;
        }, $result);
    }
}