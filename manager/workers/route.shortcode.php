<?php


/**
 * Shortcode Manager
 * -----------------
 */

Route::accept($config->manager->slug . '/shortcode', function() use($config, $speak) {
    if(Guardian::get('status') !== 'pilot') {
        Shield::abort();
    }
    $shortcodes = Get::state_shortcode(null, array(), false);
    $G = array('data' => $shortcodes);
    Config::set(array(
        'page_title' => $speak->shortcodes . $config->title_separator . $config->manager->title,
        'files' => $shortcodes,
        'cargo' => 'cargo.shortcode.php'
    ));
    if($request = Request::post()) {
        Guardian::checkToken($request['token']);
        $data = array();
        for($i = 0, $keys = $request['key'], $count = count($keys); $i < $count; ++$i) {
            if(trim($keys[$i]) !== "") {
                $data[$keys[$i]] = $request['value'][$i];
            }
        }
        $P = array('data' => $data);
        File::serialize($data)->saveTo(STATE . DS . 'shortcode.txt', 0600);
        Notify::success(Config::speak('notify_success_updated', $speak->shortcode));
        Weapon::fire('on_shortcode_update', array($G, $P));
        Guardian::kick($config->url_current);
    }
    Shield::lot('segment', 'shortcode')->attach('manager');
});