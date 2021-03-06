<?php

/**
 * =============================================================
 *  SUCH MECHA . VERY ARRAY . MANY FUNCTION(S)
 * =============================================================
 *
 * -- CODE: ----------------------------------------------------
 *
 *    Mecha::eat($array)->shake()->vomit();
 *
 * -------------------------------------------------------------
 *
 */

class Mecha extends Base {

    protected static $stomach = array();
    protected static $index = 0;

    // Prevent `$e` exceeds the value of `$min` and `$max`
    public static function edge($e, $min = 0, $max = 9999) {
        if($e < $min) $e = $min;
        if($e > $max) $e = $max;
        return $e;
    }

    // Handle missing array variable(s)
    public static function extend(&$default, $alternate) {
        $default = array_replace_recursive($default, $alternate);
        return $default;
    }

    // Convert array to object
    public static function O($a) {
        return is_array($a) ? (object) array_map('self::O', $a) : $a;
    }

    // Convert object to array
    public static function A($o) {
        return is_object($o) ? array_map('self::A', (array) $o) : $o;
    }

    // Set array value recursively
    public static function SVR(&$array, $segments, $value = "") {
        $segments = explode('.', $segments);
        while(count($segments) > 1) {
            $segment = array_shift($segments);
            if( ! array_key_exists($segment, $array)) {
                $array[$segment] = array();
            }
            $array =& $array[$segment];
        }
        $array[array_shift($segments)] = $value;
    }

    // Get array value recursively
    public static function GVR(&$array, $segments = null, $fallback = false) {
        if(is_null($segments)) {
            return $array;
        }
        foreach(explode('.', $segments) as $segment) {
            if( ! is_array($array) || ! array_key_exists($segment, $array)) {
                return $fallback;
            }
            $array =& $array[$segment];
        }
        return $array;
    }

    // Unset array value recursively
    public static function UVR(&$array, $segments) {
        $segments = explode('.', $segments);
        while(count($segments) > 1) {
            $segment = array_shift($segments);
            if(array_key_exists($segment, $array)) {
                $array =& $array[$segment];
            }
        }
        if(is_array($array) && array_key_exists($segment = array_shift($segments), $array)) {
            unset($array[$segment]);
        }
    }

    // Initialize with eating
    public static function eat($array) {
        self::$stomach = $array;
        self::$index = 0;
        return new static;
    }

    // Walk through the array
    public static function walk($array, $fn = null) {
        if(is_callable($fn)) {
            foreach($array as $k => &$v) {
                $v = call_user_func($fn, $v, $k);
            }
            unset($v);
            return $array;
        }
        return self::eat($array);
    }

    // Check if `$array` contain `$key` -- should be faster than `in_array($key, $array)`
    public static function has($key, $x = ';;;') {
        return strpos($x . implode($x, self::$stomach) . $x, $x . $key . $x) !== false;
    }

    // Sort array based on its value's key
    public static function order($order = 'ASC', $key = null, $preserve_key = false, $default = '\0') {
        if( ! is_null($key)) {
            $before = array();
            $after = array();
            if(self::$stomach && ! empty(self::$stomach)) {
                foreach(self::$stomach as $k => $v) {
                    $v = (array) $v;
                    if(array_key_exists($key, $v)) {
                        $before[$k] = $v[$key];
                    } else if($default !== '\0') {
                        $before[$k] = $default;
                        self::$stomach[$k][$key] = $default;
                    }
                }
                if($order === 'DESC') {
                    arsort($before);
                } else {
                    asort($before);
                }
                foreach($before as $k => $v) {
                    $after[$k] = self::$stomach[$k];
                }
            }
            self::$stomach = $after;
            unset($before, $after);
        } else {
            if($order === 'DESC') {
                arsort(self::$stomach);
            } else {
                asort(self::$stomach);
            }
        }
        if( ! $preserve_key) {
            self::$stomach = array_values(self::$stomach);
        }
        return new static;
    }

    // Array shake
    public static function shake() {
        shuffle(self::$stomach);
        return new static;
    }

    // Vomit! BLARGH!
    public static function vomit($param = null, $fallback = false) {
        return self::GVR(self::$stomach, $param, $fallback);
    }

    // Move to next array index
    public static function next($skip = 0) {
        self::$index = self::edge(self::$index + 1 + $skip, 0, self::count() - 1);
        return new static;
    }

    // Move to previous array index
    public static function prev($skip = 0) {
        self::$index = self::edge(self::$index - 1 - $skip, 0, self::count() - 1);
        return new static;
    }

    // Move to `$index` array index
    public static function to($index) {
        self::$index = is_int($index) ? $index : self::index($index, $index);
        return new static;
    }

    // Insert `$food` before current array index
    public static function before($food, $key = null) {
        if(is_null($key)) $key = self::$index;
        self::$stomach = array_slice(self::$stomach, 0, self::$index, true) + array($key => $food) + array_slice(self::$stomach, self::$index, null, true);
        self::$index = self::edge(self::$index - 1, 0, self::count() - 1);
        return new static;
    }

    // Insert `$food` after current array index
    public static function after($food, $key = null) {
        if(is_null($key)) $key = self::$index + 1;
        self::$stomach = array_slice(self::$stomach, 0, self::$index + 1, true) + array($key => $food) + array_slice(self::$stomach, self::$index + 1, null, true);
        self::$index = self::edge(self::$index + 1, 0, self::count() - 1);
        return new static;
    }

    // Replace current array index value with `$food`
    public static function replace($food) {
        $i = 0;
        foreach(self::$stomach as $k => $v) {
            if($i === self::$index) {
                self::$stomach[$k] = $food;
                break;
            }
            $i++;
        }
        return new static;
    }

    // Append `$food` to array
    public static function append($food, $key = null) {
        self::$index = self::count() - 1;
        return self::after($food, $key);
    }

    // Prepend `$food` to array
    public static function prepend($food, $key = null) {
        self::$index = 0;
        return self::before($food, $key);
    }

    // Get first array value
    public static function first() {
        self::$index = 0;
        return reset(self::$stomach);
    }

    // Get last array value
    public static function last() {
        self::$index = self::count() - 1;
        return end(self::$stomach);
    }

    // Get current array index
    public static function current() {
        return self::$index;
    }

    // Get selected array value
    public static function get($index = null, $fallback = false) {
        if( ! is_null($index)) {
            if(is_int($index)) {
                $index = self::key($index, $index);
            }
            return array_key_exists($index, self::$stomach) ? self::$stomach[$index] : $fallback;
        }
        $i = 0;
        foreach(self::$stomach as $k => $v) {
            if($i === self::$index) {
                return self::$stomach[$k];
            }
            $i++;
        }
    }

    // Get array length
    public static function count($data = null) {
        return is_null($data) ? count(self::$stomach) : count($data);
    }

    // Generate chunk(s) of array
    public static function chunk($index = null, $count = 25) {
        if( ! is_array(self::$stomach)) return new static;
        $results = array();
        // 0-based index with `vomit($index)`
        // `Mecha::eat($foo)->chunk(25)->vomit(1);`
        if(func_num_args() === 1) {
            $count = $index;
            $index = null;
        }
        $chunk = array_chunk(self::$stomach, $count, true);
        // 1-based index with `chunk($index)`
        // `Mecha::eat($foo)->chunk(2, 25)->vomit();`
        if( ! is_null($index)) {
            $chunk = isset($chunk[$index - 1]) ? $chunk[$index - 1] : false;
            self::$stomach = $chunk ? array_values($chunk) : array();
        // `Mecha::eat($foo)->chunk(null, 25)->vomit()`
        } else {
            self::$stomach = $chunk;
        }
        return new static;
    }

    // Shortcut for string-based `switch` and `case`
    public static function alter($case, $cases, $default = null) {
        if(is_null($default)) $default = $case;
        return array_key_exists($case, $cases) ? $cases[$case] : $default;
    }

    // Get array key by position
    public static function key($index, $fallback = false) {
        $array = array_keys(self::$stomach);
        return isset($array[$index]) ? $array[$index] : $fallback;
    }

    // Get position by array key
    public static function index($key, $fallback = false) {
        $key = array_search($key, array_keys(self::$stomach));
        return $key !== false ? $key : $fallback;
    }

}