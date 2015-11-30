<?php


/**
 * Menu Manager
 * ------------
 */

Route::accept($config->manager->slug . '/menu', function() use($config, $speak) {
    if( ! Guardian::happy(1)) {
        Shield::abort();
    }
    $menus = Get::state_menu();
    $menus_raw = Converter::toText($menus);
    Config::set(array(
        'page_title' => $speak->menus . $config->title_separator . $config->manager->title,
        'cargo' => 'cargo.menu.php'
    ));
    $G = array('data' => array('content' => $menus_raw));
    if($request = Request::post()) {
        Guardian::checkToken($request['token']);
        // Check for invalid input
        if(preg_match('#(^|\n)(\t| {1,3})(?:[^ ])#', $request['content'])) {
            Notify::error($speak->notify_invalid_indent_character);
            Guardian::memorize($request);
        }
        $P = array('data' => $request);
        if( ! Notify::errors()) {
            File::serialize(Converter::toArray($request['content'], S, '    '))->saveTo(STATE . DS . 'menu.txt', 0600);
            Notify::success(Config::speak('notify_success_updated', $speak->menu));
            Weapon::fire('on_menu_update', array($G, $P));
            Guardian::kick($config->url_current);
        }
    }
    Shield::lot(array(
        'segment' => 'menu',
        'content' => $menus_raw
    ))->attach('manager');
});