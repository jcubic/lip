

var re_re = /^\/((?:\\\/|[^/]|\[[^\]]*\/[^\]]*\])+)\/([gimy]*)$/;
var int_re = /^[-+]?[0-9]+([eE][-+]?[0-9]+)?$/;
var float_re = /^([-+]?((\.[0-9]+|[0-9]+\.[0-9]+)([eE][-+]?[0-9]+)?)|[0-9]+\.)$/;
// ----------------------------------------------------------------------
function parse_argument(arg) {
    function parse_string(string) {
        // remove quotes if before are even number of slashes
        // we don't remove slases becuase they are handled by JSON.parse
        //string = string.replace(/([^\\])['"]$/, '$1');
        if (string.match(/^['"]/)) {
            if (string === '""' || string === "''") {
                return '';
            }
            var quote = string[0];
            var re = new RegExp("((^|[^\\\\])(?:\\\\\\\\)*)" + quote, "g");
            string = string.replace(re, "$1");
        }
        // use build in function to parse rest of escaped characters
        return JSON.parse('"' + string.replace(/\n/g, '\\n') + '"');
    }
    var regex = arg.match(re_re);
    if (regex) {
        return new RegExp(regex[1], regex[2]);
    } else if (arg.match(/['"]/)) {
        return parse_string(arg);
    } else if (arg.match(int_re)) {
        return LNumber(parseFloat(arg));
    } else if (arg.match(float_re)) {
        return LNumber(parseFloat(arg), true);
    } else if (arg === 'nil') {
        return nil;
    } else if (arg === 'true') {
        return true;
    } else if (arg === 'false') {
        return false;
    } else {
        return new Symbol(arg);
    }
}
// ----------------------------------------------------------------------
/* eslint-disable */
var pre_parse_re = /("(?:\\[\S\s]|[^"])*"|\/(?! )[^\/\\]*(?:\\[\S\s][^\/\\]*)*\/[gimy]*(?=\s|\(|\)|$)|;.*)/g;
var string_re = /"(?:\\[\S\s]|[^"])*"/g;
//var tokens_re = /("(?:\\[\S\s]|[^"])*"|\/(?! )[^\/\\]*(?:\\[\S\s][^\/\\]*)*\/[gimy]*(?=\s|\(|\)|$)|\(|\)|'|"(?:\\[\S\s]|[^"])+|\n|(?:\\[\S\s]|[^"])*"|;.*|(?:[-+]?(?:(?:\.[0-9]+|[0-9]+\.[0-9]+)(?:[eE][-+]?[0-9]+)?)|[0-9]+\.)[0-9]|\.{2,}|\.|,@|,|#|`|[^(\s)]+)/gim;
// ----------------------------------------------------------------------
function makeTokenRe() {
    var tokens = Object.keys(specials).map(escapeRegex).join('|');
    return new RegExp(`("(?:\\\\[\\S\\s]|[^"])*"|\\/(?! )[^\\/\\\\]*(?:\\\\[\\S\\s][^\\/\\\\]*)*\\/[gimy]*(?=\\s|\\(|\\)|$)|\\(|\\)|'|"(?:\\\\[\\S\\s]|[^"])+|\\n|(?:\\\\[\\S\\s]|[^"])*"|;.*|(?:[-+]?(?:(?:\\.[0-9]+|[0-9]+\\.[0-9]+)(?:[eE][-+]?[0-9]+)?)|[0-9]+\\.)[0-9]|\\.{2,}|${tokens}|[^(\\s)]+)`, 'gim');
}
/* eslint-enable */
// ----------------------------------------------------------------------
function lastItem(array, n = 1) {
    return array[array.length - n];
}
// ----------------------------------------------------------------------
function escapeRegex(str) {
    if (typeof str === 'string') {
        var special = /([-\\^$[\]()+{}?*.|])/g;
        return str.replace(special, '\\$1');
    }
}
// ----------------------------------------------------------------------
function tokens(str) {
    var tokens_re = makeTokenRe();
    str = str.replace(/\n\r|\r/g, '\n');
    var count = 0;
    var line = 0;
    var tokens = [];
    var current_line = [];
    var col = 0;
    str.split(pre_parse_re).filter(Boolean).forEach(function(string) {
        if (string.match(pre_parse_re)) {
            col = 0;
            if (current_line.length) {
                var lastToken = lastItem(current_line);
                if (lastToken.token.match(/\n/)) {
                    var last_line = lastToken.token.split('\n').pop();
                    col += last_line.length;
                } else {
                    col += lastToken.token.length;
                }
                col += lastToken.col;
            }
            var token = {
                col,
                line,
                token: string,
                offset: count
            };
            tokens.push(token);
            current_line.push(token);
            count += string.length;
            col += string.length;
            line += (string.match("\n") || []).length;
            return;
        }
        string.split(tokens_re).filter(Boolean).forEach(function(string) {
            var token = {
                col,
                line,
                token: string,
                offset: count
            };
            col += string.length;
            count += string.length;
            tokens.push(token);
            current_line.push(token);
            if (string === '\n') {
                ++line;
                current_line = [];
                col = 0;
            }
        });
    });
    return tokens;
}
// ----------------------------------------------------------------------
function tokenize(str, extra) {
    if (extra) {
        return tokens(str);
    } else {
        return tokens(str).map(function(token) {
            return token.token.trim();
        }).filter(function(token) {
            return token && !token.match(/^;/);
        });
    }
}
// ----------------------------------------------------------------------
var specials = {
    "'": new Symbol('quote'),
    '`': new Symbol('quasiquote'),
    ',@': new Symbol('unquote-splicing'),
    ',': new Symbol('unquote')
};
// ----------------------------------------------------------------------
// :: tokens are the array of strings from tokenizer
// :: the return value is lisp code created out of Pair class
// ----------------------------------------------------------------------
function parse(tokens) {
    if (typeof tokens === 'string') {
        throw new Error('parse require tokenized array of tokens not string');
    }
    var stack = [];
    var result = [];
    var special = null;
    var special_tokens = Object.keys(specials);
    var special_forms = special_tokens.map(s => specials[s].name);
    var parents = 0;
    var first_value = false;
    var specials_stack = [];
    var single_list_specials = [];
    var special_count = 0;
    function pop_join() {
        var top = stack[stack.length - 1];
        if (top instanceof Array && top[0] instanceof Symbol &&
            special_forms.includes(top[0].name) &&
            stack.length > 1 && !top[0].literal) {
            stack.pop();
            if (stack[stack.length - 1].length === 1 &&
                stack[stack.length - 1][0] instanceof Symbol) {
                stack[stack.length - 1].push(top);
            } else if (stack[stack.length - 1].length === 0) {
                stack[stack.length - 1] = top;
            } else if (stack[stack.length - 1] instanceof Pair) {
                if (stack[stack.length - 1].cdr instanceof Pair) {
                    stack[stack.length - 1] = new Pair(
                        stack[stack.length - 1],
                        Pair.fromArray(top)
                    );
                } else {
                    stack[stack.length - 1].cdr = Pair.fromArray(top);
                }
            } else {
                stack[stack.length - 1].push(top);
            }
        }
    }
    tokens.forEach(function(token) {
        var top = stack[stack.length - 1];
        if (special_tokens.indexOf(token) !== -1) {
            special_count++;
            special = token;
            stack.push([specials[special]]);
            if (!special) {
                single_list_specials = [];
            }
            single_list_specials.push(special);
        } else {
            if (special) {
                specials_stack.push(single_list_specials);
                single_list_specials = [];
            }
            if (token === '(') {
                first_value = true;
                parents++;
                stack.push([]);
                special = null;
                special_count = 0;
            } else if (token === '.' && !first_value) {
                stack[stack.length - 1] = Pair.fromArray(top);
            } else if (token === ')') {
                parents--;
                if (!stack.length) {
                    throw new Error('Unbalanced parenthesis');
                }
                if (stack.length === 1) {
                    result.push(stack.pop());
                } else if (stack.length > 1) {
                    var list = stack.pop();
                    top = stack[stack.length - 1];
                    if (top instanceof Array) {
                        top.push(list);
                    } else if (top instanceof Pair) {
                        top.append(Pair.fromArray(list));
                    }
                    if (specials_stack.length) {
                        single_list_specials = specials_stack.pop();
                        while (single_list_specials.length) {
                            pop_join();
                            single_list_specials.pop();
                        }
                    } else {
                        pop_join();
                    }
                }
                if (parents === 0 && stack.length) {
                    result.push(stack.pop());
                }
            } else {
                first_value = false;
                var value = parse_argument(token);
                if (special) {
                    // special without list like ,foo
                    while (special_count--) {
                        stack[stack.length - 1][1] = value;
                        value = stack.pop();
                    }
                    special_count = 0;
                    special = false;
                } else if (value instanceof Symbol &&
                           special_forms.includes(value.name)) {
                    // handle parsing os special forms as literal symbols
                    // (values they expand into)
                    value.literal = true;
                }
                top = stack[stack.length - 1];
                if (top instanceof Pair) {
                    var node = top;
                    while (true) {
                        if (node.cdr === nil) {
                            if (value instanceof Array) {
                                node.cdr = Pair.fromArray(value);
                            } else {
                                node.cdr = value;
                            }
                            break;
                        } else {
                            node = node.cdr;
                        }
                    }
                } else if (!stack.length) {
                    result.push(value);
                } else {
                    top.push(value);
                }
            }
        }
    });
    if (stack.length) {
        dump(result);
        throw new Error('Unbalanced parenthesis 2');
    }
    return result.map((arg) => {
        if (arg instanceof Array) {
            return Pair.fromArray(arg);
        }
        return arg;
    });
}

// ----------------------------------------------------------------------
// detect if object is ES6 Symbol that work with polyfills
// ----------------------------------------------------------------------
function isSymbol(x) {
    return typeof x === 'symbol' ||
        typeof x === 'object' &&
        Object.prototype.toString.call(x) === '[object Symbol]';
}
function Symbol(name) {
    if (typeof this !== 'undefined' && this.constructor !== Symbol ||
        typeof this === 'undefined') {
        return new Symbol(name);
    }
    this.name = name;
}
// ----------------------------------------------------------------------
Symbol.is = function(symbol, name) {
    return symbol instanceof Symbol &&
        ((typeof name === 'string' && symbol.name === name) ||
         (name instanceof RegExp && name.test(symbol.name)));
};
// ----------------------------------------------------------------------
Symbol.prototype.toJSON = Symbol.prototype.toString = function() {
    //return '<#symbol \'' + this.name + '\'>';
    if (isSymbol(this.name)) {
        return this.name.toString().replace(/^Symbol\(([^)]+)\)/, '$1');
    }
    return this.name;
};
// ----------------------------------------------------------------------
// :: Nil constructor with only once instance
// ----------------------------------------------------------------------
function Nil() {}
Nil.prototype.toString = function() {
    return 'nil';
};
var nil = new Nil();
// ----------------------------------------------------------------------
// :: Pair constructor
// ----------------------------------------------------------------------
function Pair(car, cdr) {
    if (typeof this !== 'undefined' && this.constructor !== Pair ||
        typeof this === 'undefined') {
        return new Pair(car, cdr);
    }
    this.car = car;
    this.cdr = cdr;
}

// ----------------------------------------------------------------------
function emptyList() {
    return new Pair(undefined, nil);
}
// ----------------------------------------------------------------------
Pair.prototype.flatten = function() {
    return Pair.fromArray(flatten(this.toArray()));
};
// ----------------------------------------------------------------------
Pair.prototype.length = function() {
    if (isEmptyList(this)) {
        return 0;
    }
    var len = 0;
    var node = this;
    while (true) {
        if (!node || node === nil || !(node instanceof Pair) ||
             node.haveCycles('cdr')) {
            break;
        }
        len++;
        node = node.cdr;
    }
    return len;
};

// ----------------------------------------------------------------------
Pair.prototype.clone = function() {
    var visited = new Map();
    function clone(node) {
        if (node instanceof Pair) {
            if (visited.has(node)) {
                return visited.get(node);
            }
            var pair = new Pair();
            visited.set(node, pair);
            pair.car = clone(node.car);
            pair.cdr = clone(node.cdr);
            return pair;
        }
        return node;
    }
    return clone(this);
};

// ----------------------------------------------------------------------
Pair.prototype.toArray = function() {
    if (this.isEmptyList()) {
        return [];
    }
    var result = [];
    if (this.car instanceof Pair) {
        result.push(this.car.toArray());
    } else {
        result.push(this.car);
    }
    if (this.cdr instanceof Pair) {
        result = result.concat(this.cdr.toArray());
    }
    return result;
};

// ----------------------------------------------------------------------
Pair.prototype.isEmptyList = function() {
    return typeof this.car === 'undefined' && this.cdr === nil;
};

// ----------------------------------------------------------------------
Pair.fromArray = function(array) {
    if (array instanceof Pair) {
        return array;
    }
    if (array.length && !(array instanceof Array)) {
        array = [...array];
    }
    if (array.length === 0) {
        return emptyList();
    } else {
        var car;
        if (array[0] instanceof Array) {
            car = Pair.fromArray(array[0]);
        } else {
            car = array[0];
        }
        if (array.length === 1) {
            return new Pair(car, nil);
        } else {
            return new Pair(car, Pair.fromArray(array.slice(1)));
        }
    }
};

// ----------------------------------------------------------------------
Pair.prototype.toObject = function() {
    var node = this;
    var result = {};
    while (true) {
        if (node instanceof Pair && node.car instanceof Pair) {
            var pair = node.car;
            var name = pair.car;
            if (name instanceof Symbol) {
                name = name.name;
            }
            var cdr = pair.cdr;
            if (cdr instanceof Pair) {
                cdr = cdr.toObject();
            }
            if (cdr instanceof LNumber) {
                cdr = cdr.valueOf();
            }
            result[name] = cdr;
            node = node.cdr;
        } else {
            break;
        }
    }
    return result;
};

// ----------------------------------------------------------------------
Pair.fromPairs = function(array) {
    return array.reduce((list, pair) => {
        return new Pair(
            new Pair(
                new Symbol(pair[0]),
                pair[1]
            ),
            list
        );
    }, nil);
};

// ----------------------------------------------------------------------
Pair.fromObject = function(obj) {
    var array = Object.keys(obj).map((key) => [key, obj[key]]);
    return Pair.fromPairs(array);
};

// ----------------------------------------------------------------------
Pair.prototype.reduce = function(fn) {
    var node = this;
    var result = nil;
    while (true) {
        if (node !== nil) {
            result = fn(result, node.car);
            node = node.cdr;
        } else {
            break;
        }
    }
    return result;
};

// ----------------------------------------------------------------------
Pair.prototype.reverse = function() {
    if (this.haveCycles()) {
        throw new Error("You can't reverse list that have cycles");
    }
    var node = this;
    var prev = nil;
    while (node !== nil) {
        var next = node.cdr;
        node.cdr = prev;
        prev = node;
        node = next;
    }
    return prev;
};

// ----------------------------------------------------------------------
Pair.prototype.transform = function(fn) {
    var visited = [];
    function recur(pair) {
        if (pair instanceof Pair) {
            if (pair.replace) {
                delete pair.replace;
                return pair;
            }
            var car = fn(pair.car);
            if (car instanceof Pair) {
                car = recur(car);
                visited.push(car);
            }
            var cdr = fn(pair.cdr);
            if (cdr instanceof Pair) {
                cdr = recur(cdr);
                visited.push(cdr);
            }
            return new Pair(car, cdr);
        }
        return pair;
    }
    return recur(this);
};

// ----------------------------------------------------------------------
Pair.prototype.map = function(fn) {
    if (typeof this.car !== 'undefined') {
        return new Pair(fn(this.car), isEmptyList(this.cdr) ? nil : this.cdr.map(fn));
    } else {
        return nil;
    }
};

// ----------------------------------------------------------------------
function toString(value) {
    if (typeof value === 'function') {
        return '<#function ' + (value.name || 'anonymous') + '>';
    } else if (typeof value === 'string') {
        return JSON.stringify(value);
    } else if (isPromise(value)) {
        return '<#Promise>';
    } else if (value instanceof Symbol ||
              value instanceof LNumber ||
              value instanceof Pair ||
              value === nil) {
        return value.toString();
    } else if (value instanceof Array) {
        return value.map(toString);
    } else if (typeof value === 'object') {
        if (value === null) {
            return 'null';
        }
        var name = value.constructor.name;
        if (name === 'Object') {
            return JSON.stringify(value);
        }
        return '<#object(' + value.constructor.name + ')>';
    } else if (typeof value !== 'undefined') {
        return value;
    }
}

// ----------------------------------------------------------------------------
Pair.prototype.markCycles = function() {
    markCycles(this);
    return this;
};

// ----------------------------------------------------------------------------
Pair.prototype.haveCycles = function(name = null) {
    if (!name) {
        return this.haveCycles('car') || this.haveCycles('cdr');
    }
    return !!(this.cycles && this.cycles[name]);
};

// ----------------------------------------------------------------------------
function markCycles(pair) {
    var seenPairs = [];
    var cycles = [];
    function cycleName(pair) {
        if (pair instanceof Pair) {
            if (seenPairs.includes(pair)) {
                if (!cycles.includes(pair)) {
                    cycles.push(pair);
                }
                return `#${cycles.length - 1}#`;
            }
        }
    }
    function detect(pair) {
        if (pair instanceof Pair) {
            seenPairs.push(pair);
            var cycles = {};
            var carCycle = cycleName(pair.car);
            var cdrCycle = cycleName(pair.cdr);
            if (carCycle) {
                cycles['car'] = carCycle;
            } else {
                detect(pair.car);
            }
            if (cdrCycle) {
                cycles['cdr'] = cdrCycle;
            } else {
                detect(pair.cdr);
            }
            if (carCycle || cdrCycle) {
                pair.cycles = cycles;
            } else if (pair.cycles) {
                delete pair.cycles;
            }
        }
    }
    detect(pair);
}

// ----------------------------------------------------------------------
Pair.prototype.toString = function() {
    var arr = ['('];
    if (this.car !== undefined) {
        var value;
        if (this.cycles && this.cycles.car) {
            value = this.cycles.car;
        } else {
            value = toString(this.car);
        }
        if (value) {
            arr.push(value);
        }
        if (this.cdr instanceof Pair) {
            if (this.cycles && this.cycles.cdr) {
                arr.push(' . ');
                arr.push(this.cycles.cdr);
            } else {
                var name;
                if (this.cycles && this.cycles.cdr) {
                    name = this.cycles.cdr;
                }
                var cdr = this.cdr.toString(name).replace(/^\(|\)$/g, '');
                arr.push(' ');
                arr.push(cdr);
            }
        } else if (typeof this.cdr !== 'undefined' && this.cdr !== nil) {
            if (typeof this.cdr === 'string') {
                arr = arr.concat([' . ', JSON.stringify(this.cdr)]);
            } else {
                arr = arr.concat([' . ', toString(this.cdr)]);
            }
        }
    }
    arr.push(')');
    return arr.join('');
};

// ----------------------------------------------------------------------
Pair.prototype.set = function(prop, value) {
    this[prop] = value;
    if (value instanceof Pair) {
        this.markCycles();
    }
};

// ----------------------------------------------------------------------
Pair.prototype.append = function(pair) {
    if (pair instanceof Array) {
        return this.append(Pair.fromArray(pair));
    }
    var p = this;
    if (p.car === undefined) {
        if (pair instanceof Pair) {
            this.car = pair.car;
            this.cdr = pair.cdr;
        } else {
            this.car = pair;
        }
    } else {
        while (true) {
            if (p instanceof Pair && p.cdr !== nil) {
                p = p.cdr;
            } else {
                break;
            }
        }
        if (pair instanceof Pair) {
            p.cdr = pair;
        } else if (pair !== nil) {
            p.cdr = new Pair(pair, nil);
        }
    }
    return this;
};

// ----------------------------------------------------------------------
function equal(x, y) {
    if (x instanceof LNumber && y instanceof LNumber) {
        return x.cmp(y) === 0;
    } else if (typeof x === 'number' || typeof y === 'number') {
        return LNumber(x).cmp(LNumber(y));
    } else if (x instanceof Symbol && y instanceof Symbol) {
        return x.name === y.name;
    } else {
        return x === y;
    }
}
// ----------------------------------------------------------------------
function isEmptyList(x) {
    return x instanceof Pair && x.isEmptyList() || x === nil;
}