<?php

class Form extends Cell {

    // `<input>`
    public static function input($type = 'text', $name = null, $value = null, $placeholder = null, $attr = array(), $indent = 0) {
        if(strpos($name, '.') === 0) {
            $attr['disabled'] = true;
            $name = ltrim($name, '.');
        }
        $attr['name'] = $name;
        $attr['value'] = self::protect($value);
        $attr['placeholder'] = $placeholder;
        $attr['type'] = $type;
        return self::unit('input', false, $attr, $indent);
    }

    // `<button>`
    public static function button($text = "", $name = null, $type = null, $value = null, $attr = array(), $indent = 0) {
        if(strpos($name, '.') === 0) {
            $attr['disabled'] = true;
            $name = ltrim($name, '.');
        }
        $attr['name'] = $name;
        $attr['type'] = $type;
        $attr['value'] = $value;
        return self::unit('button', $text, $attr, $indent);
    }

    // `<select>`
    public static function select($name = null, $option = array(), $select = null, $attr = array(), $indent = 0) {
        $o = "";
        if(strpos($name, '.') === 0) {
            $attr['disabled'] = true;
            $name = ltrim($name, '.');
        }
        $attr['name'] = $name;
        foreach($option as $key => $value) {
            // option list group
            if(is_array($value)) {
                $attr_o = array();
                if(strpos($key, '.') === 0) {
                    $attr_o['disabled'] = true;
                    $key = ltrim($key, '.');
                }
                $attr_o['label'] = $key;
                $o .= NL . self::begin('optgroup', $attr_o, $indent + 1);
                foreach($value as $k => $v) {
                    $attr_o = array();
                    if(strpos($k, '.') === 0) {
                        $attr_o['disabled'] = true;
                        $k = ltrim($k, '.');
                    }
                    if(ltrim($select, '.') === (string) $k) {
                        $attr_o['selected'] = true;
                    }
                    $attr_o['value'] = $k;
                    $o .= NL . self::unit('option', $v, $attr_o, $indent + 2);
                }
                $o .= NL . self::end();
            // option list
            } else {
                $attr_o = array();
                if(strpos($key, '.') === 0) {
                    $attr_o['disabled'] = true;
                    $key = ltrim($key, '.');
                }
                if(ltrim($select, '.') === (string) $key) {
                    $attr_o['selected'] = true;
                }
                $attr_o['value'] = $key;
                $o .= NL . self::unit('option', $value, $attr_o, $indent + 1);
            }
        }
        return self::unit('select', $o . NL . ($indent ? str_repeat(TAB, $indent) : ""), $attr, $indent);
    }

    // `<textarea>`
    public static function textarea($name = null, $content = "", $placeholder = null, $attr = array(), $indent = 0) {
        if(strpos($name, '.') === 0) {
            $attr['disabled'] = true;
            $name = ltrim($name, '.');
        }
        $attr['name'] = $name;
        $attr['placeholder'] = $placeholder;
        return self::unit('textarea', self::protect($content), $attr, $indent);
    }

}