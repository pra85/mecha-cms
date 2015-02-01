<?php

class Filter {

    protected static $filters = array();

    /**
     * ===================================================================
     *  ADD FILTER
     * ===================================================================
     *
     * -- CODE: ----------------------------------------------------------
     *
     *    Filter::add('content', function($content) {
     *        return str_replace('foo', 'bar', $content);
     *    });
     *
     * -------------------------------------------------------------------
     *
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *  Parameter  | Type    | Description
     *  ---------- | ------- | -------------------------------------------
     *  $name      | string  | Filter name
     *  $fn        | mixed   | Filter function
     *  $stack     | float   | Filter function priority
     *  ---------- | ------- | -------------------------------------------
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *
     */

    public static function add($name, $fn, $stack = 10) {
        // Kill duplicates
        if(isset(self::$filters[$name]) && is_array(self::$filters[$name])) {
            foreach(self::$filters[$name] as $filter) {
                if($filter['fn'] == $fn && $filter['stack'] === $stack) return true;
            }
        }
        self::$filters[$name][] = array(
            'fn' => $fn,
            'stack' => (float) ( ! is_null($stack) ? $stack : 10)
        );
    }

    /**
     * ===================================================================
     *  APPLY FILTER
     * ===================================================================
     *
     * -- CODE: ----------------------------------------------------------
     *
     *    Filter::apply('content', $content);
     *
     * -------------------------------------------------------------------
     *
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *  Parameter | Type   | Description
     *  --------- | ------ | ---------------------------------------------
     *  $name     | string | Filter name
     *  $value    | string | String to be manipulated
     *  --------- | ------ | ---------------------------------------------
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *
     */

    public static function apply($name, $value) {
        if( ! isset(self::$filters[$name])) {
            self::$filters[$name] = false;
            return $value;
        }
        $params = array_slice(func_get_args(), 2);
        $filters = Mecha::eat(self::$filters[$name])->order('ASC', 'stack')->vomit();
        foreach($filters as $filter => $cargo) {
            $arguments = array_merge(array($value), $params);
            $value = call_user_func_array($cargo['fn'], $arguments);
        }
        return $value;
    }

    /**
     * ===================================================================
     *  REMOVE FILTER
     * ===================================================================
     *
     * -- CODE: ----------------------------------------------------------
     *
     *    Filter::remove('content');
     *
     * -------------------------------------------------------------------
     *
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *  Parameter | Type    | Description
     *  --------- | ------- | --------------------------------------------
     *  $name     | string  | Filter name
     *  $stack    | float   | Filter function priority
     *  --------- | ------- | --------------------------------------------
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *
     */

    public static function remove($name = null, $stack = null) {
        if( ! is_null($stack)) {
            $stack = (float) $stack;
        }
        if( ! is_null($name)) {
            if( ! is_null($stack)) {
                for($i = 0, $length = count(self::$filters[$name]); $i < $length; ++$i) {
                    if(self::$filters[$name][$i]['stack'] === $stack) {
                        unset(self::$filters[$name][$i]);
                    }
                }
            } else {
                unset(self::$filters[$name]);
            }
        } else {
            self::$filters = array();
        }
    }

    /**
     * ===================================================================
     *  CHECK IF FILTER ALREADY EXIST/ADDED
     * ===================================================================
     *
     * -- CODE: ----------------------------------------------------------
     *
     *    if(Filter::exist('content')) {
     *        echo 'OK.';
     *    }
     *
     *    var_dump(Filter::exist()); // inspect!
     *
     * -------------------------------------------------------------------
     *
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *  Parameter | Type   | Description
     *  --------- | ------ | ---------------------------------------------
     *  $name     | string | Filter name
     *  $fallback | mixed  | Fallback value if filter does not exist
     *  --------- | ------ | ---------------------------------------------
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *
     */

    public static function exist($name = null, $fallback = false) {
        if(is_null($name)) {
            return ! empty(self::$filters) ? self::$filters : $fallback;
        }
        return isset(self::$filters[$name]) ? self::$filters[$name] : $fallback;
    }

}