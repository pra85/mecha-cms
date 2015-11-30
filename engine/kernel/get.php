<?php

class Get extends Base {

    /**
     * ==========================================================================
     *  GET ALL FILE(S) RECURSIVELY
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    $files = Get::files(
     *        'some/path',
     *        'txt',
     *        'ASC',
     *        'update'
     *    );
     *
     *    $files = Get::files(
     *        'some/path',
     *        'gif,jpg,jpeg,png',
     *        'ASC',
     *        'update'
     *    );
     *
     * --------------------------------------------------------------------------
     *
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *  Parameter  | Type    | Desription
     *  ---------- | ------- | --------------------------------------------------
     *  $folder    | string  | Path to folder of file(s) you want to be listed
     *  $e         | string  | The file extension(s)
     *  $order     | string  | Ascending or descending? ASC/DESC?
     *  $sorter    | string  | The key of array item as sorting reference
     *  $filter    | string  | Filter the resulted array by a keyword
     *  $inclusive | boolean | Include hidden file(s) to result(s)?
     *  ---------- | ------- | --------------------------------------------------
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *
     */

    public static function files($folder = ASSET, $e = '*', $order = 'DESC', $sorter = 'path', $filter = "", $inclusive = false) {
        if( ! file_exists($folder)) return false;
        $results = array();
        $results_inclusive = array();
        $e = $e === '*' ? '.*?' : str_replace(array(' ', ','), array("", '|'), $e);
        $folder = rtrim(File::path($folder), DS);
        $directory = new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS);
        foreach(new RegexIterator(new RecursiveIteratorIterator($directory), '#\.(' . $e . ')$#i') as $file => $object) {
            if( ! $filter || strpos(File::B($file), $filter) !== false) {
                $results_inclusive[] = File::inspect($file);
            }
            $_file = str_replace($folder . DS, "", $file);
            if(
                // Exclude all file(s) inside a folder from result(s) if the
                // folder name begins with two underscore(s). Example: `__folder-name`
                // Exclude file from result(s) if the file name begins with
                // two underscore(s). Example: `__file-name.txt`
                strpos($_file, '__') !== 0 &&
                // Linux?
                strpos($_file, '.') !== 0
            ) {
                if( ! $filter || strpos(File::B($file), $filter) !== false) {
                    $results[] = File::inspect($file);
                }
            }
        }
        if($inclusive) {
            unset($results);
            return ! empty($results_inclusive) ? Mecha::eat($results_inclusive)->order($order, $sorter)->vomit() : false;
        } else {
            unset($results_inclusive);
            return ! empty($results) ? Mecha::eat($results)->order($order, $sorter)->vomit() : false;
        }
    }

    /**
     * ==========================================================================
     *  GET ADJACENT FILE(S)
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    $files = Get::adjacentFiles(
     *        'some/path',
     *        'txt',
     *        'ASC',
     *        'update'
     *    );
     *
     * --------------------------------------------------------------------------
     *
     */

    public static function adjacentFiles($folder = ASSET, $e = '*', $order = 'DESC', $sorter = 'path', $filter = "", $inclusive = false) {
        if( ! file_exists($folder)) return false;
        $results = array();
        $results_inclusive = array();
        $e = str_replace(' ', "", $e);
        $folder = rtrim(File::path($folder), DS);
        $files = strpos($e, ',') !== false ? glob($folder . DS . '*.{' . $e . '}', GLOB_NOSORT | GLOB_BRACE) : glob($folder . DS . '*.' . $e, GLOB_NOSORT);
        if($inclusive) {
            $files = array_merge($files, glob($folder . DS . '.*', GLOB_NOSORT));
        }
        foreach($files as $file) {
            if(is_file($file)) {
                if( ! $filter || strpos(File::B($file), $filter) !== false) {
                    $results_inclusive[] = File::inspect($file);
                }
                $_file = str_replace($folder . DS, "", $file);
                if(strpos($_file, '__') !== 0 && strpos($_file, '.') !== 0) {
                    if( ! $filter || strpos(File::B($file), $filter) !== false) {
                        $results[] = File::inspect($file);
                    }
                }
            }
        }
        if($inclusive) {
            unset($results);
            return ! empty($results_inclusive) ? Mecha::eat($results_inclusive)->order($order, $sorter)->vomit() : false;
        } else {
            unset($results_inclusive);
            return ! empty($results) ? Mecha::eat($results)->order($order, $sorter)->vomit() : false;
        }
    }

    /**
     * ==========================================================================
     *  GET ALL FILE(S) RECURSIVELY INCLUDING THE EXCLUDED FILE(S)
     * ==========================================================================
     */

    public static function inclusiveFiles($folder = ASSET, $e = '*', $order = 'DESC', $sorter = 'path', $filter = "") {
        return self::files($folder, $e, $order, $sorter, $filter, true);
    }

    /**
     * ==========================================================================
     *  GET ADJACENT FILE(S) INCLUDING THE EXCLUDED FILE(S)
     * ==========================================================================
     */

    public static function inclusiveAdjacentFiles($folder = ASSET, $e = '*', $order = 'DESC', $sorter = 'path', $filter = "") {
        return self::adjacentFiles($folder, $e, $order, $sorter, $filter, true);
    }

    // Get stored configuration data (internal only)
    public static function state_config($output = null, $fallback = array()) {
        $d = WORKER . DS . 'repair.state.config.php';
        $config = file_exists($d) ? include $d : $fallback;
        if($file = File::exist(STATE . DS . 'config.txt')) {
            Mecha::extend($config, File::open($file)->unserialize());
        }
        $config = Filter::apply('state:config', $config);
        if( ! is_null($output)) {
            return isset($config[$output]) ? $config[$output] : $fallback;
        }
        return $config;
    }

    // Get stored custom field data (internal only)
    public static function state_field($scope = null, $key = null, $fallback = array(), $all = true) {
        $config = Config::get();
        $speak = Config::speak();
        $d = WORKER . DS . 'repair.state.field.php';
        $field = file_exists($d) ? include $d : $fallback;
        if($file = File::exist(STATE . DS . 'field.txt')) {
            Mecha::extend($field, File::open($file)->unserialize());
        }
        if($all) {
            // Allow shield to add custom field(s) dynamically by creating
            // a file named as `fields.php` stored in a folder named as `workers`.
            // This file contains array of field(s) data.
            //
            // -- EXAMPLE CONTENT OF `fields.php`: --------------------------------
            //
            //    return array(
            //        'break_title_text' => array(
            //            'title' => 'Break Title Text?',
            //            'type' => 'text',
            //            'value' => "",
            //            'scope' => 'article'
            //        )
            //    );
            //
            // --------------------------------------------------------------------
            //
            if($e = File::exist(SHIELD . DS . $config->shield . DS . 'workers' . DS . 'fields.php')) {
                $field_e = include $e;
                Mecha::extend($field, $field_e);
            }
            // Allow plugin to add custom field(s) dynamically by creating
            // a file named as `fields.php` stored in a folder named as `workers`.
            // This file contains array of field(s) data.
            foreach(glob(PLUGIN . DS . '*' . DS . '{__launch,launch}.php', GLOB_NOSORT | GLOB_BRACE) as $active) {
                if($e = File::exist(File::D($active) . DS . 'workers' . DS . 'fields.php')) {
                    $field_e = include $e;
                    Mecha::extend($field, $field_e);
                }
            }
        }
        $field = Converter::strEval($field);
        foreach($field as &$v) {
            if( ! isset($v['value'])) $v['value'] = "";
            if( ! isset($v['scope'])) $v['scope'] = 'article,page,comment';
        }
        unset($v);
        // Filter output(s) by `scope`
        if( ! is_null($scope)) {
            $field_alt = array();
            foreach($field as $k => $v) {
                foreach(explode(',', $scope) as $s) {
                    if(strpos(',' . $v['scope'] . ',', ',' . $s . ',') !== false) {
                        $field_alt[$k] = $v;
                    }
                }
            }
            $field = $field_alt;
            unset($field_alt);
        }
        $field = Filter::apply('state:field', $field);
        // Filter output(s) by `key`
        if( ! is_null($key)) {
            return isset($field[$key]) ? $field[$key] : $fallback;
        }
        // No filter
        return $field;
    }

    // Get stored menu data (internal only)
    public static function state_menu($fallback = array()) {
        $speak = Config::speak();
        $d = WORKER . DS . 'repair.state.menu.php';
        $menu = file_exists($d) ? include $d : $fallback;
        if($m = File::exist(STATE . DS . 'menu.txt')) {
            $m = File::open($m)->read();
            if(strpos($m, 'a:') === 0 && strpos($m, "\n") === false) {
                $menu = unserialize($m); // it's serialized
            } else {
                $menu = Converter::toArray($m, S, '    '); // YAML-like text format
            }
        }
        return Filter::apply('state:menu', $menu);
    }

    // Get stored shortcode data (internal only)
    public static function state_shortcode($key = null, $fallback = array(), $all = true) {
        $config = Config::get();
        $d = WORKER . DS . 'repair.state.shortcode.php';
        $shortcode = file_exists($d) ? include $d : $fallback;
        if($file = File::exist(STATE . DS . 'shortcode.txt')) {
            $file = File::open($file)->unserialize();
            foreach($file as $k => $v) {
                unset($shortcode[$k]);
            }
            $shortcode = array_merge($shortcode, $file);
        }
        if($all) {
            // Allow shield to add custom built-in shortcode(s) dynamically
            // by creating a file named as `shortcodes.php` stored in a folder
            // named as `workers`. This file contains array of shortcode(s) data.
            //
            // -- EXAMPLE CONTENT OF `shortcodes.php`: ----------------------------
            //
            //    return array(
            //        '{{shortcode:%s}}' => '<span>$1</span>'
            //    );
            //
            // --------------------------------------------------------------------
            //
            if($e = File::exist(SHIELD . DS . $config->shield . DS . 'workers' . DS . 'shortcodes.php')) {
                $shortcode_e = include $e;
                Mecha::extend($shortcode, $shortcode_e);
            }
            // Allow plugin to add custom built-in shortcode(s) dynamically
            // by creating a file named as `shortcodes.php` stored in a folder
            // named as `workers`. This file contains array of shortcode(s) data.
            foreach(glob(PLUGIN . DS . '*' . DS . '{__launch,launch}.php', GLOB_NOSORT | GLOB_BRACE) as $active) {
                if($e = File::exist(File::D($active) . DS . 'workers' . DS . 'shortcodes.php')) {
                    $shortcode_e = include $e;
                    Mecha::extend($shortcode, $shortcode_e);
                }
            }
        }
        $shortcode = Filter::apply('state:shortcode', Converter::strEval($shortcode));
        // Filter output(s) by `key`
        if( ! is_null($key)) {
            return isset($shortcode[$key]) ? $shortcode[$key] : $fallback;
        }
        // No filter
        return $shortcode;
    }

    // Get stored tag data (internal only)
    public static function state_tag($id = null, $fallback = array()) {
        $speak = Config::speak();
        $d = WORKER . DS . 'repair.state.tag.php';
        $tag = file_exists($d) ? include $d : $fallback;
        $tag = File::open(STATE . DS . 'tag.txt')->unserialize($tag);
        $tag = Filter::apply('state:tag', Converter::strEval($tag));
        // Filter output(s) by `id`
        if( ! is_null($id)) {
            foreach($tag as $k => $v) {
                if($v['id'] === $id) {
                    return $tag[$k];
                }
            }
        }
        // No filter
        return $tag;
    }

    /**
     * ==========================================================================
     *  EXTRACT OBJECT OF TAG(S) FROM TAG FILE
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    foreach(Get::tags() as $tag) {
     *        echo $tag->name . '<br>';
     *    }
     *
     * --------------------------------------------------------------------------
     *
     */

    public static function tags($order = 'ASC', $sorter = 'name') {
        $tags = Mecha::eat(self::state_tag())->order($order, $sorter)->vomit();
        foreach($tags as $k => $v) {
            $tags[$k] = (object) array(
                'id' => Filter::colon('tag:id', $v['id'], $tags),
                'name' => Filter::colon('tag:name', $v['name'], $tags),
                'slug' => Filter::colon('tag:slug', $v['slug'], $tags),
                'description' => Filter::colon('tag:description', $v['description'], $tags)
            );
        }
        return $tags;
    }

    /**
     * ==========================================================================
     *  RETURN SPECIFIC TAG ITEM FILTERED BY ITS AVAILABLE DATA
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    $tag = Get::tag('lorem-ipsum');
     *    echo $tag->name . '<br>';
     *
     * --------------------------------------------------------------------------
     *
     */

    public static function tag($filter, $output = null, $fallback = false) {
        $tags = self::tags();
        // alternate 2: `Get::tag('id:2', 'slug', false)`
        if(strpos($filter, ':') !== false) {
            list($key, $value) = explode(':', $filter, 2);
            foreach($tags as $k => $v) {
                $value = Converter::strEval($value);
                if(isset($tags[$k]->{$key}) && $tags[$k]->{$key} === $value) {
                    return is_null($output) ? $tags[$k] : (isset($tags[$k]->{$output}) ? $tags[$k]->{$output} : $fallback);
                }
            }
        // alternate 1: `Get::tag(2, 'slug', false)
        } else {
            foreach($tags as $k => $v) {
                if(
                    (is_numeric($filter) && (int) $filter === (int) $tags[$k]->id) || // by ID
                    (is_string($filter) && (string) $filter === (string) $tags[$k]->slug) || // by slug
                    (is_string($filter) && (string) $filter === (string) $tags[$k]->name) // by name
                ) {
                    return is_null($output) ? $tags[$k] : (isset($tags[$k]->{$output}) ? $tags[$k]->{$output} : $fallback);
                }
            }
        }
        return $fallback;
    }

    /**
     * ==========================================================================
     *  GET POST PATH
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    var_dump(Get::postPath('lorem-ipsum'));
     *
     * --------------------------------------------------------------------------
     *
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *  Parameter | Type  | Description
     *  --------- | ----- | -----------------------------------------------------
     *  $detector | mixed | Slug, ID or time of the post
     *  --------- | ----- | -----------------------------------------------------
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *
     */

    public static function postPath($detector, $folder = POST) {
        foreach(glob($folder . DS . '*.{txt,draft,archive}', GLOB_NOSORT | GLOB_BRACE) as $path) {
            list($time, $kind, $slug) = explode('_', File::N($path), 3);
            if($slug === $detector || $time === Date::slug($detector)) {
                return $path;
            }
        }
        return false;
    }

    /**
     * ==========================================================================
     *  GET LIST OF POST DETAIL(S)
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    var_dump(Get::postExtract($input));
     *
     * --------------------------------------------------------------------------
     *
     */

    public static function postExtract($input, $FP = 'post:') {
        if( ! $input) return false;
        $e = File::E($input);
        $update = File::T($input);
        $update_date = ! is_null($update) ? date('Y-m-d H:i:s', $update) : null;
        list($time, $kind, $slug) = explode('_', File::N($input), 3);
        $kind = explode(',', $kind);
        return array(
            'path' => Filter::colon($FP . 'path', $input, $input),
            'id' => Filter::colon($FP . 'id', (int) Date::format($time, 'U'), $input),
            'time' => Filter::colon($FP . 'time', Date::format($time), $input),
            'update_raw' => Filter::colon($FP . 'update_raw', $update, $input),
            'update' => Filter::colon($FP . 'update', $update_date, $input),
            'kind' => Filter::colon($FP . 'kind', Converter::strEval($kind), $input),
            'slug' => Filter::colon($FP . 'slug', $slug, $input),
            'state' => Filter::colon($FP . 'state', Mecha::alter($e, array(
                'txt' => 'published',
                'draft' => 'drafted',
                'archive' => 'archived'
            )), $input)
        );
    }

    /**
     * ==========================================================================
     *  GET LIST OF POST(S) PATH
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    foreach(Get::posts() as $path) {
     *        echo $path . '<br>';
     *    }
     *
     *    // [1]. Filter by Tag(s) ID
     *    Get::posts('DESC', 'kind:2');
     *    Get::posts('DESC', 'kind:2,3,4');
     *
     *    // [2]. Filter by Time
     *    Get::posts('DESC', 'time:2014');
     *    Get::posts('DESC', 'time:2014-11');
     *    Get::posts('DESC', 'time:2014-11-10');
     *
     *    // [3]. Filter by Slug
     *    Get::posts('DESC', 'slug:lorem');
     *    Get::posts('DESC', 'slug:lorem-ipsum');
     *
     *    // [4]. The Old Way(s)
     *    Get::posts('DESC', 'lorem');
     *    Get::posts('DESC', 'lorem-ipsum');
     *
     *    // [5]. The Old Way(s)' Alias
     *    Get::posts('DESC', 'keyword:lorem');
     *    Get::posts('DESC', 'keyword:lorem-ipsum');
     *
     * --------------------------------------------------------------------------
     *
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *  Parameter | Type   | Description
     *  --------- | ------ | ----------------------------------------------------
     *  $order    | string | Ascending or descending? ASC/DESC?
     *  $filter   | string | Filter the resulted array by a keyword
     *  $e        | string | The file extension(s)
     *  $folder   | string | Folder of the post(s)
     *  --------- | ------ | ----------------------------------------------------
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *
     */

    public static function posts($order = 'DESC', $filter = "", $e = 'txt', $folder = POST) {
        $results = array();
        $e = str_replace(' ', "", $e);
        $pages = strpos($e, ',') !== false ? glob($folder . DS . '*.{' . $e . '}', GLOB_NOSORT | GLOB_BRACE) : glob($folder . DS . '*.' . $e, GLOB_NOSORT);
        $total_pages = count($pages);
        if( ! is_array($pages) || $total_pages === 0) return false;
        if($order === 'DESC') {
            rsort($pages);
        } else {
            sort($pages);
        }
        if( ! $filter) return $pages;
        if(strpos($filter, ':') !== false) {
            list($key, $value) = explode(':', $filter, 2);
            if($key === 'time') {
                for($i = 0; $i < $total_pages; ++$i) {
                    list($time, $kind, $slug) = explode('_', File::N($pages[$i]), 3);
                    if(strpos($time, $value) === 0) {
                        $results[] = $pages[$i];
                    }
                }
            } else if($key === 'kind') {
                $kinds = explode(',', $value);
                for($i = 0; $i < $total_pages; ++$i) {
                    $name = str_replace('_', ',', File::N($pages[$i]));
                    foreach($kinds as $kind) {
                        if(strpos($name, ',' . $kind . ',') !== false) {
                            $results[] = $pages[$i];
                        }
                    }
                }
            } else if($key === 'slug') {
                for($i = 0; $i < $total_pages; ++$i) {
                    list($time, $kind, $slug) = explode('_', File::N($pages[$i]), 3);
                    if(strpos($slug, $value) !== false) {
                        $results[] = $pages[$i];
                    }
                }
            } else { // if($key === 'keyword') {
                for($i = 0; $i < $total_pages; ++$i) {
                    if(strpos(File::N($pages[$i]), $value) !== false) {
                        $results[] = $pages[$i];
                    }
                }
            }
        } else {
            for($i = 0; $i < $total_pages; ++$i) {
                if(strpos(File::N($pages[$i]), $filter) !== false) {
                    $results[] = $pages[$i];
                }
            }
        }
        unset($pages);
        return ! empty($results) ? $results : false;
    }

    /**
     * ==========================================================================
     *  GET LIST OF POST(S) DETAIL(S)
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    foreach(Get::postsExtract() as $file) {
     *        echo $file['path'] . '<br>';
     *    }
     *
     *    Get::postsExtract('DESC', 'time', 'kind:2');
     *    Get::postsExtract('DESC', 'time', 'kind:2,3,4');
     *
     * --------------------------------------------------------------------------
     *
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *  Parameter | Type   | Description
     *  --------- | ------ | ----------------------------------------------------
     *  $sorter   | string | The key of array item as sorting reference
     *  --------- | ------ | ----------------------------------------------------
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *
     */

    public static function postsExtract($order = 'DESC', $sorter = 'time', $filter = "", $e = 'txt', $FP = 'page:', $folder = POST) {
        if($files = self::posts($order, $filter, $e, $folder)) {
            $results = array();
            foreach($files as $file) {
                $results[] = self::postExtract($file, $FP);
            }
            unset($files);
            return ! empty($results) ? Mecha::eat($results)->order($order, $sorter)->vomit() : false;
        }
        return false;
    }

    /**
     * ==========================================================================
     *  GET MINIMUM DATA OF A POST
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    var_dump(Get::postAnchor('about'));
     *
     * --------------------------------------------------------------------------
     *
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *  Parameter  | Type   | Description
     *  ---------- | ------ | ---------------------------------------------------
     *  $path      | string | The URL path of the post file, or a post slug
     *  $folder    | string | Folder of the post(s)
     *  $connector | string | See `Get::post()`
     *  $FP        | string | See `Get::post()`
     *  ---------- | ------ | ---------------------------------------------------
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *
     */

    public static function postAnchor($path, $folder = POST, $connector = '/', $FP = 'post:') {
        if(strpos($path, ROOT) === false) {
            $path = self::postPath($path, $folder); // By post slug, ID or time
        }
        if($path && ($buffer = File::open($path)->get(1)) !== false) {
            $results = self::postExtract($path, $FP);
            $parts = explode(S, $buffer, 2);
            $results['url'] = Filter::colon($FP . 'url', Config::get('url') . $connector . $results['slug'], $results);
            $v = isset($parts[1]) ? Converter::DS(trim($parts[1])) : "";
            $results['title_raw'] = Filter::colon($FP . 'title_raw', $v, $results);
            $results['title'] = Filter::colon($FP . 'title', $v, $results);
            return Mecha::O($results);
        }
        return false;
    }

    /**
     * ==========================================================================
     *  GET POST HEADER(S) ONLY
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    var_dump(Get::postHeader('lorem-ipsum'));
     *
     * --------------------------------------------------------------------------
     *
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *  Parameter  | Type   | Description
     *  ---------- | ------ | ---------------------------------------------------
     *  $path      | string | The URL path of the post file, or a post slug
     *  $folder    | string | Folder of the post(s)
     *  $connector | string | See `Get::post()`
     *  $FP        | string | See `Get::post()`
     *  ---------- | ------ | ---------------------------------------------------
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *
     */

    public static function postHeader($path, $folder = POST, $connector = '/', $FP = 'post:') {
        $config = Config::get();
        if(strpos($path, ROOT) === false) {
            $path = self::postPath($path, $folder); // By page slug, ID or time
        }
        if( ! $path) return false;
        $results = self::postExtract($path, $FP);
        $results = $results + Text::toPage($path, false, $FP, array(
            'link' => "",
            'author' => $config->author->name,
            'description' => "",
            'content_type' => $config->html_parser,
            'fields' => array()
        ), $results);
        $results['date'] = Filter::colon($FP . 'date', Date::extract($results['time']), $results);
        $results['url'] = Filter::colon($FP . 'url', $config->url . $connector . $results['slug'], $results);
        $fields = self::state_field(rtrim($FP, ':'), null, array(), false);
        $init = array();
        foreach($fields as $key => $value) {
            $init[$key] = $value['value'];
        }
        if(isset($results['fields']) && is_array($results['fields'])) {
            foreach($results['fields'] as $key => $value) {
                if(is_array($value) && isset($value['type'])) {
                    $value = isset($value['value']) ? $value['value'] : false;
                }
                $init[$key] = Filter::colon($FP . 'fields.' . $key, $value, $results);
            }
            $results['fields'] = $init;
        }
        return Mecha::O($results);
    }

    /**
     * ==========================================================================
     *  EXTRACT POST FILE INTO LIST OF POST DATA FROM ITS PATH/SLUG/ID
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    var_dump(Get::post('about'));
     *
     * --------------------------------------------------------------------------
     *
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *  Parameter  | Type   | Description
     *  ---------- | ------ | ---------------------------------------------------
     *  $reference | mixed  | Slug, ID, path or array of `Get::postExtract()`
     *  $excludes  | array  | Exclude some field(s) from result(s)
     *  $folder    | string | Folder of the post(s)
     *  $connector | string | Path connector for post URL
     *  $FP        | string | Filter prefix for `Text::toPage()`
     *  ---------- | ------ | ---------------------------------------------------
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *
     */

    public static function post($reference, $excludes = array(), $folder = POST, $connector = '/', $FP = 'post:') {
        $config = Config::get();
        $speak = Config::speak();
        $excludes = array_flip($excludes);
        $results = false;
        if( ! is_array($reference)) {
            // By slug => `post-slug` or by ID => `1403355917`
            if(strpos($reference, $folder) !== 0) {
                $reference = self::postPath($reference, $folder);
            }
            // By path => `lot\posts\$folder\2014-06-21-20-05-17_1,2,3_page-slug.txt`
            $results = self::postExtract($reference, $FP);
        } else {
            // From `Get::postExtract()`
            $results = $reference;
        }
        if( ! $results || ! file_exists($results['path'])) return false;
        // RULES: Do not do any tags looping, content parsing
        // and external file requesting if it has been marked as
        // the excluded field(s). For better performance.
        $results = $results + Text::toPage(File::open($results['path'])->read(), isset($excludes['content']) ? false : 'content', $FP, array(
            'link' => "",
            'author' => $config->author->name,
            'description' => "",
            'content_type' => $config->html_parser,
            'fields' => array(),
            'content' => ""
        ), $results);
        $content = $results['content_raw'];
        $time = str_replace(array(' ', ':'), '-', $results['time']);
        $e = File::E($results['path']);
        // Custom post content with PHP file, named as the post slug
        if($php = File::exist(File::D($results['path']) . DS . $results['slug'] . '.php')) {
            ob_start();
            include $php;
            $results['content'] = ob_get_clean();
        }
        $results['date'] = Filter::colon($FP . 'date', Date::extract($results['time']), $results);
        $results['url'] = Filter::colon($FP . 'url', $config->url . $connector . $results['slug'], $results);
        $results['excerpt'] = $more = "";
        if($content !== "") {
            $exc = isset($excludes['content']) && strpos($content, '<!--') !== false ? Text::toPage(Converter::ES($content), 'content', $FP, array(), $results) : $results;
            $exc = $exc['content'];
            $exc = is_array($exc) ? implode("", $exc) : $exc;
            // Generate fake description data
            if($results['description'] === "") {
                $results['description'] = Converter::curt($exc, $config->excerpt->length, $config->excerpt->tail);
            }
            // Manual post excerpt with `<!-- cut+ "Read More" -->`
            if(strpos($exc, '<!-- cut+ ') !== false) {
                preg_match('#<!-- cut\+( +([\'"]?)(.*?)\2)? -->#', $exc, $matches);
                $more = ! empty($matches[3]) ? $matches[3] : $speak->read_more;
                $more = '<p><a class="fi-link" href="' . $results['url'] . '#' . sprintf($config->excerpt->id, $results['id']) . '">' . $more . '</a></p>';
                $exc = preg_replace('#<!-- cut\+( +(.*?))? -->#', '<!-- cut -->', $exc);
            }
            // ... or `<!-- cut -->`
            if(strpos($exc, '<!-- cut -->') !== false) {
                $parts = explode('<!-- cut -->', $exc, 2);
                $results['excerpt'] = Filter::colon($FP . 'excerpt', trim($parts[0]) . $more, $results);
                $results['content'] = trim($parts[0]) . NL . NL . '<span class="fi" id="' . sprintf($config->excerpt->id, $results['id']) . '" aria-hidden="true"></span>' . NL . NL . trim($parts[1]);
            }
        }
        // Post Tags
        if( ! isset($excludes['tags'])) {
            $tags = array();
            foreach($results['kind'] as $id) {
                $tags[] = self::tag('id:' . $id);
            }
            $results['tags'] = Filter::colon($FP . 'tags', Mecha::eat($tags)->order('ASC', 'name')->vomit(), $results);
        }
        // Post Images
        $results['images'] = Filter::colon($FP . 'images', self::imagesURL($results['content']), $results);
        $results['image'] = Filter::colon($FP . 'image', isset($results['images'][0]) ? $results['images'][0] : Image::placeholder(), $results);
        // Custom Field(s)
        if( ! isset($excludes['fields'])) {
            // Initialize custom field(s) with the default value(s) so that
            // user(s) don't have to write `isset()` function multiple time(s)
            // just to prevent error message(s) because of the object key(s)
            // that is not available in the old post(s).
            $fields = self::state_field(rtrim($FP, ':'), null, array(), false);
            $init = array();
            foreach($fields as $key => $value) {
                $init[$key] = $value['value'];
            }
            // Start re-writing ...
            if(isset($results['fields']) && is_array($results['fields'])) {
                foreach($results['fields'] as $key => $value) {
                    // [1]. `Fields: {"my_field":{"type":"t","value":"foo"}}`
                    // [2]. `Fields: {"my_field":"foo"}`
                    if(is_array($value) && isset($value['type'])) {
                        $value = isset($value['value']) ? $value['value'] : false;
                    }
                    $init[$key] = Filter::colon($FP . 'fields.' . $key, $value, $results);
                }
            }
            $results['fields'] = $init;
            unset($fields, $init);
        }
        // Exclude some field(s) from result(s)
        foreach($results as $key => $value) {
            if(isset($excludes[$key])) {
                unset($results[$key]);
            }
        }
        return Mecha::O($results);
    }

    /**
     * ==========================================================================
     *  GET RESPONSE PATH
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    var_dump(Get::responsePath(1399334470));
     *
     * --------------------------------------------------------------------------
     *
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *  Parameter | Type  | Description
     *  --------- | ----- | -----------------------------------------------------
     *  $detector | mixed | Slug, ID or time of the page
     *  --------- | ----- | -----------------------------------------------------
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *
     */

    public static function responsePath($detector, $folder = RESPONSE) {
        foreach(glob($folder . DS . '*.{txt,hold}', GLOB_NOSORT | GLOB_BRACE) as $path) {
            list($post, $time, $parent) = explode('_', File::N($path), 3);
            if($time === $detector || (string) $time === Date::slug($detector)) {
                return $path;
            }
        }
        return false;
    }

    /**
     * ==========================================================================
     *  GET LIST OF RESPONSE DETAIL(S)
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    var_dump(Get::responseExtract($input));
     *
     * --------------------------------------------------------------------------
     *
     */

    public static function responseExtract($input, $FP = 'response:') {
        if( ! $input) return false;
        $e = File::E($input);
        $update = File::T($input);
        $update_date = ! is_null($update) ? date('Y-m-d H:i:s', $update) : null;
        list($post, $id, $parent) = explode('_', File::N($input), 3);
        return array(
            'path' => Filter::colon($FP . 'path', $input, $input),
            'time' => Filter::colon($FP . 'time', Date::format($id), $input),
            'update_raw' => Filter::colon($FP . 'update_raw', $update, $input),
            'update' => Filter::colon($FP . 'update', $update_date, $input),
            'post' => Filter::colon($FP . 'post', (int) Date::format($post, 'U'), $input),
            'id' => Filter::colon($FP . 'id', (int) Date::format($id, 'U'), $input),
            'parent' => Filter::colon($FP . 'parent', $parent === '0000-00-00-00-00-00' ? null : (int) Date::format($parent, 'U'), $input),
            'state' => Filter::colon($FP . 'state', Mecha::alter($e, array(
                'txt' => 'approved',
                'hold' => 'pending'
            )), $input)
        );
    }

    /**
     * ===========================================================================
     *  GET LIST OF RESPONSE(S) PATH
     * ===========================================================================
     *
     * -- CODE: ------------------------------------------------------------------
     *
     *    foreach(Get::responses() as $path) {
     *        echo $path . '<br>';
     *    }
     *
     *    // [1]. Filter by Post Time
     *    Get::responses('DESC', 'post:2014');
     *    Get::responses('DESC', 'post:2014-04');
     *    Get::responses('DESC', 'post:2014-04-21');
     *
     *    // [2]. Filter by Response Time
     *    Get::responses('DESC', 'time:2014');
     *    Get::responses('DESC', 'time:2014-11');
     *    Get::responses('DESC', 'time:2014-11-10');
     *
     *    // [3]. Filter by Response Parent Time
     *    Get::responses('DESC', 'parent:2014');
     *    Get::responses('DESC', 'parent:2014-04');
     *    Get::responses('DESC', 'parent:2014-04-21');
     *
     * ---------------------------------------------------------------------------
     *
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *  Parameter | Type    | Description
     *  --------- | ------- | ----------------------------------------------------
     *  $order    | string  | Ascending or descending? ASC/DESC?
     *  $filter   | string  | The result(s) filter
     *  $e        | boolean | The file extension(s)
     *  --------- | ------- | ----------------------------------------------------
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *
     */

    public static function responses($order = 'ASC', $filter = "", $e = 'txt', $folder = RESPONSE) {
        $results = array();
        $e = str_replace(' ', "", $e);
        $responses = strpos($e, ',') !== false ? glob($folder . DS . '*.{' . $e . '}', GLOB_NOSORT | GLOB_BRACE) : glob($folder . DS . '*.' . $e, GLOB_NOSORT);
        $total_responses = count($responses);
        if( ! is_array($responses) || $total_responses === 0) return false;
        if($order === 'DESC') {
            rsort($responses);
        } else {
            sort($responses);
        }
        if( ! $filter) return $responses;
        if(strpos($filter, ':') !== false) {
            list($key, $value) = explode(':', $filter, 2);
            if($key === 'post') {
                for($i = 0; $i < $total_responses; ++$i) {
                    list($post, $time, $parent) = explode('_', File::N($responses[$i]), 3);
                    if(strpos($post, $value) === 0) {
                        $results[] = $responses[$i];
                    }
                }
            } else if($key === 'time') {
                for($i = 0; $i < $total_responses; ++$i) {
                    list($post, $time, $parent) = explode('_', File::N($responses[$i]), 3);
                    if(strpos($time, $value) === 0) {
                        $results[] = $responses[$i];
                    }
                }
            } else if($key === 'parent') {
                for($i = 0; $i < $total_responses; ++$i) {
                    list($post, $time, $parent) = explode('_', File::N($responses[$i]), 3);
                    if(strpos($parent, $value) === 0) {
                        $results[] = $responses[$i];
                    }
                }
            } else { // if($key === 'keyword') {
                for($i = 0; $i < $total_responses; ++$i) {
                    if(strpos(File::N($responses[$i]), $value) !== false) {
                        $results[] = $responses[$i];
                    }
                }
            }
        } else {
            for($i = 0; $i < $total_responses; ++$i) {
                if(strpos(File::N($responses[$i]), $filter) !== false) {
                    $results[] = $responses[$i];
                }
            }
        }
        unset($responses);
        return ! empty($results) ? $results : false;
    }

    /**
     * ==========================================================================
     *  GET LIST OF RESPONSE(S) DETAIL(S)
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    foreach(Get::responsesExtract() as $file) {
     *        echo $file['path'] . '<br>';
     *    }
     *
     * --------------------------------------------------------------------------
     *
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *  Parameter | Type   | Description
     *  --------- | ------ | ----------------------------------------------------
     *  $sorter   | string | The key of array item as sorting reference
     *  --------- | ------ | ----------------------------------------------------
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *
     */

    public static function responsesExtract($order = 'ASC', $sorter = 'time', $filter = "", $e = 'txt', $FP = 'response:', $folder = RESPONSE) {
        if($files = self::responses($order, $filter, $e, $folder)) {
            $results = array();
            foreach($files as $file) {
                $results[] = self::responseExtract($file, $FP);
            }
            unset($files);
            return ! empty($results) ? Mecha::eat($results)->order($order, $sorter)->vomit() : false;
        }
        return false;
    }

    /**
     * ==========================================================================
     *  GET MINIMUM DATA OF A RESPONSE
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    var_dump(Get::responseAnchor(1399334470));
     *
     * --------------------------------------------------------------------------
     *
     */

    public static function responseAnchor($path, $folder = array(RESPONSE, POST), $FP = 'response:') {
        if(strpos($path, ROOT) === false) {
            $path = self::responsePath($path, $folder[0]); // By post slug, ID or time
        }
        if($path && ($buffer = File::open($path)->get(1)) !== false) {
            $results = self::responseExtract($path, $FP);
            $parts = explode(S, $buffer, 2);
            $results['name_raw'] = Filter::colon($FP . 'name_raw', isset($parts[1]) ? Converter::DS(trim($parts[1])) : "", $results);
            $results['name'] = Filter::colon($FP . 'name', $results['name_raw'], $results);
            return Mecha::O($results);
        }
        return false;
    }

    /**
     * ==========================================================================
     *  GET RESPONSE HEADER(S) ONLY
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    var_dump(Get::responseHeader(1399334470));
     *
     * --------------------------------------------------------------------------
     *
     */

    public static function responseHeader($path, $folder = array(RESPONSE, POST), $connector = '/', $FP = 'response:') {
        $config = Config::get();
        if(strpos($path, ROOT) === false) {
            $path = self::responsePath($path, $folder[0]); // By response ID or time
        }
        if( ! $path) return false;
        $results = self::responseExtract($path, $FP);
        $results = $results + Text::toPage($path, false, $FP, array(
            'url' => '#',
            'content_type' => $config->html_parser
        ), $results);
        $results['date'] = Filter::colon($FP . 'date', Date::extract($results['time']), $results);
        $fields = self::state_field(rtrim($FP, ':'), null, array(), false);
        $init = array();
        foreach($fields as $key => $value) {
            $init[$key] = $value['value'];
        }
        if(isset($results['fields']) && is_array($results['fields'])) {
            foreach($results['fields'] as $key => $value) {
                if(is_array($value) && isset($value['type'])) {
                    $value = isset($value['value']) ? $value['value'] : false;
                }
                $init[$key] = Filter::colon($FP . 'fields.' . $key, $value, $results);
            }
            $results['fields'] = $init;
        }
        return Mecha::O($results);
    }

    /**
     * ==========================================================================
     *  EXTRACT RESPONSE FILE INTO LIST OF RESPONSE DATA FROM ITS PATH/ID/TIME
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    var_dump(Get::response(1399334470));
     *
     * --------------------------------------------------------------------------
     *
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *  Parameter  | Type   | Description
     *  ---------- | ------ | ---------------------------------------------------
     *  $reference | string | Response path, ID or time
     *  $excludes  | array  | Exclude some field(s) from result(s)
     *  $folder    | string | Folder of response(s) and response(s)' post
     *  $connector | string | Path connector for permalink URL
     *  $FP        | string | Filter prefix for `Text::toPage()`
     *  ---------- | ------ | ---------------------------------------------------
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *
     */

    public static function response($reference, $excludes = array(), $folder = array(), $connector = '/', $FP = 'response:') {
        $config = Config::get();
        $excludes = array_flip($excludes);
        $results = false;
        if( ! is_array($reference)) {
            // By time => `2014-06-21-20-05-17` or by ID => `1403355917`
            if(strpos($reference, $folder[0]) !== 0) {
                $reference = self::responsePath($reference, $folder[0]);
            }
            // By path => `lot\responses\$folder[0]\2014-05-24-11-17-06_2014-06-21-20-05-17_0000-00-00-00-00-00.txt`
            $results = self::responseExtract($reference, $FP);
        } else {
            // From `Get::responseExtract()`
            $results = $reference;
        }
        if( ! $results || ! file_exists($results['path'])) return false;
        $results['date'] = Filter::colon($FP . 'date', Date::extract($results['time']), $results);
        $results = $results + Text::toPage(File::open($results['path'])->read(), 'message', $FP, array(
            'url' => '#',
            'content_type' => $config->html_parser
        ), $results);
        if( ! isset($excludes['permalink'])) {
            if($path = self::postPath($results['post'], $folder[1])) {
                $link = self::postAnchor($path, $folder[1], $connector, "")->url . '#' . rtrim($FP, ':') . '-' . $results['id'];
            } else {
                $link = '#';
            }
            $results['permalink'] = Filter::colon($FP . 'permalink', $link, $results);
        }
        if( ! isset($excludes['fields'])) {
            $fields = self::state_field(rtrim($FP, ':'), null, array(), false);
            $init = array();
            foreach($fields as $key => $value) {
                $init[$key] = $value['value'];
            }
            if(isset($results['fields']) && is_array($results['fields'])) {
                foreach($results['fields'] as $key => $value) {
                    if(is_array($value) && isset($value['type'])) {
                        $value = isset($value['value']) ? $value['value'] : false;
                    }
                    $init[$key] = Filter::colon($FP . 'fields.' . $key, $value, $results);
                }
                $results['fields'] = $init;
                unset($fields, $init);
            }
        }
        foreach($results as $key => $value) {
            if(isset($excludes[$key])) {
                unset($results[$key]);
            }
        }
        return Mecha::O($results);
    }

    /**
     * ==========================================================================
     *  GET IMAGE(S) URL FROM TEXT SOURCE
     * ==========================================================================
     *
     * -- CODE: -----------------------------------------------------------------
     *
     *    var_dump(Get::imagesURL('some text', 'no-image.png'));
     *
     * --------------------------------------------------------------------------
     *
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *  Parameter | Type   | Description
     *  --------- | ------ | ----------------------------------------------------
     *  $source   | string | The source text
     *  $fallback | string | Fallback image URL if nothing matched
     *  --------- | ------ | ----------------------------------------------------
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *
     */

    public static function imagesURL($source, $fallback = array()) {
        $config = Config::get();
        $results = array();
        // Matched with ...
        //
        // [1]. `![alt text](IMAGE URL)`
        // [2]. `![alt text](IMAGE URL "optional title")`
        //
        // ... and the single-quoted version of them
        if(preg_match_all('#\!\[.*?\]\(([^\s]+?)( +([\'"]).*?\3)?\)#', $source, $matches)) {
            $results = array_merge($matches[1], $results);
        }
        // Matched with ...
        //
        // [1]. `<img src="IMAGE URL">`
        // [2]. `<img foo="bar" src="IMAGE URL">`
        // [3]. `<img src="IMAGE URL" foo="bar">`
        // [4]. `<img src="IMAGE URL"/>`
        // [5]. `<img foo="bar" src="IMAGE URL"/>`
        // [6]. `<img src="IMAGE URL" foo="bar"/>`
        // [7]. `<img src="IMAGE URL" />`
        // [8]. `<img foo="bar" src="IMAGE URL" />`
        // [9]. `<img src="IMAGE URL" foo="bar" />`
        //
        // ... and the uppercased version of them, and the single-quoted version of them
        if(preg_match_all('#<img .*?src=([\'"])([^\'"]+?)\1.*? *\/?>#i', $source, $matches)) {
            $results = array_merge($matches[2], $results);
        }
        // Matched with ...
        //
        // [1]. `background: url("IMAGE URL")`
        // [2]. `background-image: url("IMAGE URL")`
        // [3]. `background: foo url("IMAGE URL")`
        // [4]. `background-image: foo url("IMAGE URL")`
        // [5]. `content: url("IMAGE URL")`
        //
        // ... and the uppercased version of them, and the single-quoted version of them, and the un-quoted version of them
        if(preg_match_all('#(background-image|background|content)\: *.*?url\(([\'"]?)([^\'"]+?)\2\)#i', $source, $matches)) {
            $results = array_merge($matches[3], $results);
        }
        // Validate URL ...
        foreach(array_unique($results) as $k => $url) {
            $url = strpos($url, '/') === 0 ? $config->protocol . $config->host . $url : $url;
            if(strpos($url, $config->url) === 0 && file_exists(File::path($url))) {
                $results[$k] = $url;
            } else if(strpos($url, '://') !== false) {
                $results[$k] = $url;
            }
        }
        return ! empty($results) ? $results : $fallback;
    }

    /**
     * ==========================================================================
     *  GET IMAGE URL FROM TEXT SOURCE
     * ==========================================================================
     *
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *  Parameter | Type    | Description
     *  --------- | ------- | ---------------------------------------------------
     *  $source   | string  | The source text
     *  $sequence | integer | Sequence of available image URLs
     *  $fallback | string  | Fallback image URL if nothing matched
     *  --------- | ------- | ---------------------------------------------------
     * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
     *
     */

    public static function imageURL($source, $sequence = 1, $fallback = null) {
        $images = self::imagesURL($source, array());
        return isset($images[$sequence - 1]) ? $images[$sequence - 1] : (is_null($fallback) ? Image::placeholder() : $fallback);
    }

}