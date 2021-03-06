<?php defined('ROOT') or die;


/**
 * Error Reporting
 * ---------------
 */

ini_set('error_log', LOG . DS . 'errors.log');

if(DEBUG) {
    error_reporting(E_ALL | E_STRICT);
    ini_set('display_errors', TRUE);
    ini_set('display_startup_errors', TRUE);
    ini_set('html_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', FALSE);
    ini_set('display_startup_errors', FALSE);
}


/**
 * => `http://www.php.net/manual/en/security.magicquotes.disabling.php`
 * --------------------------------------------------------------------
 */

$gpc = array(&$_GET, &$_POST, &$_REQUEST, &$_COOKIE);

array_walk_recursive($gpc, function(&$value) {
    $value = str_replace(array("\r\n", "\r"), "\n", $value);
    if(get_magic_quotes_gpc()) {
        $value = stripslashes($value);
    }
});


/**
 * Loading Worker(s)
 * -----------------
 */

spl_autoload_register(function($worker) {
    $path = ENGINE . DS . 'kernel' . DS . strtolower($worker) . '.php';
    if(file_exists($path)) require $path;
});


/**
 * Loading Plug(s)
 * ---------------
 */

foreach(glob(ENGINE . DS . 'plug' . DS . '*.php', GLOB_NOSORT) as $plug) {
    require $plug;
}


/**
 * Start the Session(s)
 * --------------------
 */

Session::start();


/**
 * Load the Configuration Data
 * ---------------------------
 */

Config::load();

$config = Config::get();
$speak = Config::speak();


/**
 * Include User Defined Function(s)
 * --------------------------------
 */

if($function = File::exist(SHIELD . DS . $config->shield . DS . 'functions.php')) {
    include $function;
}


/**
 * Loading Shield
 * --------------
 */

$__ = SHIELD . DS . $config->shield;
$s = File::open($__ . 'states' . DS . 'config.txt')->unserialize();
Config::set('states.shield_' . md5($config->shield), $s);
Config::set('states.shield', $s); // current shield
$config = Config::get(); // refresh ...
if( ! $language = File::exist($__ . 'languages' . DS . $config->language . DS . 'speak.txt')) {
    $language = $__ . 'languages' . DS . 'en_US' . DS . 'speak.txt';
}

if(file_exists($language)) {
    Config::merge('speak', Text::toArray(File::open($language)->read(), S, '  '));
    $speak = Config::speak(); // refresh ...
}


/**
 * Define Allowed File Extension(s)
 * --------------------------------
 */

$e = explode(',', FONT_EXT . ',' . IMAGE_EXT . ',' . MEDIA_EXT . ',' . PACKAGE_EXT . ',' . SCRIPT_EXT);
File::$config['file_extension_allow'] = array_unique($e);


/**
 * Set Default Time Zone
 * ---------------------
 */

Date::timezone($config->timezone);


/**
 * Set Page Meta
 * -------------
 */

function do_meta_1() {
    $config = Config::get();
    $speak = Config::speak();
    $html  = O_BEGIN . Cell::meta(null, null, array('charset' => $config->charset)) . NL;
    $html .= Cell::meta('viewport', 'width=device-width', array(), 2) . NL;
    $html .= Cell::meta('generator', 'Powered by Mecha ' . MECHA_VERSION, array(), 2) . NL;
    if($config->page_type !== '404' && isset($config->{$config->page_type}->description)) {
        $config->description = $config->{$config->page_type}->description;
    }
    $html .= Cell::meta('description', Text::parse($config->description, '->text'), array(), 2) . NL;
    $html .= Cell::meta('author', Text::parse($config->author->name, '->text'), array(), 2) . NL;
    echo Filter::apply('meta', $html, 1);
}

function do_meta_2() {
    $config = Config::get();
    $html  = Cell::title(Text::parse($config->page_title, '->text'), array(), 2) . NL;
    $html .= Cell::__('[if IE]>' . Cell::script($config->protocol . 'html5shiv.googlecode.com/svn/trunk/html5.js') . '<![endif]', 2, "") . NL;
    echo Filter::apply('meta', $html, 2);
}

function do_meta_3() {
    $config = Config::get();
    $speak = Config::speak();
    $html  = Cell::link(Filter::colon('favicon:url', $config->url . '/favicon.ico'), 'shortcut icon', 'image/x-icon', array(), 2) . NL;
    $html .= Cell::link(Filter::colon('canonical:url', $config->url_current), 'canonical', null, array(), 2) . NL;
    $html .= Cell::link(Filter::colon('sitemap:url', $config->url . '/sitemap'), 'sitemap', null, array(), 2) . NL;
    $html .= Cell::link(Filter::colon('feed:url', $config->url . '/feed/rss'), 'alternate', 'application/rss+xml', array(
        'title' => $speak->feeds . $config->title_separator . $config->title
    ), 2) . O_END;
    echo Filter::apply('meta', $html, 3);
}

Weapon::add('meta', 'do_meta_1', 10);
Weapon::add('meta', 'do_meta_2', 20);
Weapon::add('meta', 'do_meta_3', 30);

Weapon::add('SHIPMENT_REGION_TOP', function() {
    Weapon::fire('meta');
}, 10);


/**
 * Inject Widget's CSS and JavaScript
 * ----------------------------------
 */

if($config->widget_include_css) {
    Weapon::add('shell_before', function() {
        echo Asset::stylesheet(SHIELD . DS . 'widgets.css', "", 'shell/widgets.min.css');
    });
}

if($config->widget_include_js) {
    Weapon::add('SHIPMENT_REGION_BOTTOM', function() {
        echo Asset::javascript(SHIELD . DS . 'widgets.js', "", 'sword/widgets.min.js');
    });
}


/**
 * Loading Plugin(s)
 * -----------------
 */

Weapon::fire('plugins_before');

foreach($plugins = Plugin::load() as $k => $v) {
    $__ = PLUGIN . DS . $k . DS;
    Config::set('states.plugin_' . md5($k), File::open($__ . 'states' . DS . 'config.txt')->unserialize());
    $config = Config::get(); // refresh ...
    if( ! $language = File::exist($__ . 'languages' . DS . $config->language . DS . 'speak.txt')) {
        $language = $__ . 'languages' . DS . 'en_US' . DS . 'speak.txt';
    }
    if(file_exists($language)) {
        Config::merge('speak', Text::toArray(File::open($language)->read(), S, '  '));
        $speak = Config::speak(); // refresh ...
    }
    $load__ = Request::get('plugin:' . $k, 1);
    if( ! Guardian::happy()) $load__ = 1; // force 1 for passenger(s)
    if($load__ !== false && $load__ > 0) {
        Weapon::fire(array('plugin_before', 'plugin_' . md5($k) . '_before'));
        if($launch = File::exist($__ . 'launch.php')) {
            if(strpos(File::B($__), '__') === 0) {
                if(Guardian::happy() && $config->page_type === 'manager') {
                    include $launch; // backend
                }
            } else {
                include $launch; // frontend
            }
        }
        if($launch = File::exist($__ . '__launch.php')) {
            if(Guardian::happy() && $config->page_type === 'manager') {
                include $launch; // backend
            }
        }
        Weapon::fire(array('plugin_after', 'plugin_' . md5($k) . '_after'));
    }
}

Weapon::fire('plugins_after');


/**
 * Check the Plugin(s) Order
 * -------------------------
 */

// var_dump($plugins); exit;


/**
 * Loading Menu(s)
 * ---------------
 */

foreach(Get::state_menu() as $key => $value) {
    Menu::add($key, $value);
}


/**
 * Handle Shortcode(s) in Content
 * ------------------------------
 */

function do_shortcode($content) {
    if(strpos($content, '{{') === false) return $content;
    foreach(Get::state_shortcode() as $key => $value) {
        $key = preg_quote($key, '#');
        // %[a,b,c]: option(s) ... accept `a`, `b`, or `c`
        if(strpos($key, '%\\[') !== false) {
            $key = preg_replace_callback('#%\\\\\[(.*?)\\\\\]#', function($matches) {
                return '(' . str_replace(array(',', '&\\#44;'), array('|', ','), $matches[1]) . ')';
            }, $key);
        }
        // %s: accept any value(s) without line break(s)
        // %m: accept any value(s) with/without line break(s)
        // %i: accept integer number(s)
        // %f: accept float number(s)
        // %b: accept boolean value(s)
        $key = str_replace(
            array('%s', '%m', '%i', '%f', '%b'),
            array('(.+?)', '([\s\S]+?)', '(\d+?)', '((?:\d*\.)?\d+?)', '\b(TRUE|FALSE|YES|NO|Y|N|ON|OFF|true|false|yes|no|y|n|on|off|1|0|\+|\-)\b'),
        $key);
        $content = preg_replace('#(?<!`)' . $key . '|' . $key . '(?!`)#', Converter::DW($value), $content);
    }
    return $content;
}

function do_shortcode_x($content) {
    if(strpos($content, '`{{') === false) return $content;
    return str_replace(array('`{{', '}}`'), array('{{', '}}'), $content);
}

Filter::add('shortcode', 'do_shortcode', 20);

// YOU ARE HERE! -- Specify your own shortcode priority to be greater
// than the default shortcode file priority, but lesser than the shortcode
// deactivation priority by determining the shortcode priority between 20 - 30

Filter::add('shortcode', 'do_shortcode_x', 30);