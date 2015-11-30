<?php


/**
 * Article Manager
 * ---------------
 */

Route::accept(array($config->manager->slug . '/article', $config->manager->slug . '/article/(:num)'), function($offset = 1) use($config, $speak) {
    $articles = false;
    $offset = (int) $offset;
    if($files = Mecha::eat(Get::articles('DESC', "", 'txt,draft,archive'))->chunk($offset, $config->manager->per_page)->vomit()) {
        $articles = array();
        foreach($files as $file) {
            $articles[] = Get::articleHeader($file);
        }
        unset($files);
    } else {
        if($offset !== 1) Shield::abort();
    }
    Config::set(array(
        'page_title' => $speak->articles . $config->title_separator . $config->manager->title,
        'pages' => $articles,
        'offset' => $offset,
        'pagination' => Navigator::extract(Get::articles('DESC', "", 'txt,draft,archive'), $offset, $config->manager->per_page, $config->manager->slug . '/article'),
        'cargo' => 'cargo.post.php'
    ));
    Shield::lot(array('segment' => 'article'))->attach('manager');
});


/**
 * Article Composer/Updater
 * ------------------------
 */

Route::accept(array($config->manager->slug . '/article/ignite', $config->manager->slug . '/article/repair/id:(:num)'), function($id = false) use($config, $speak) {
    if($id && $article = Get::article($id, array('content', 'excerpt', 'tags'))) {
        $extension_o = $article->state === 'published' ? '.txt' : '.draft';
        if( ! Guardian::happy(1) && Guardian::get('author') !== $article->author) {
            Shield::abort();
        }
        if( ! isset($article->link)) $article->link = "";
        if( ! isset($article->fields)) $article->fields = array();
        if( ! isset($article->content_type)) $article->content_type = $config->html_parser;
        if( ! File::exist(CUSTOM . DS . Date::slug($article->date->unix) . $extension_o)) {
            $article->css_raw = $config->defaults->article_css;
            $article->js_raw = $config->defaults->article_js;
        }
        // Remove fake article description data from article composer
        $test = explode(SEPARATOR, str_replace("\r", "", file_get_contents($article->path)), 2);
        if(strpos($test[0], "\n" . 'Description' . S . ' ') === false) {
            $article->description = "";
        }
        unset($test);
        $title = $speak->editing . ': ' . $article->title . $config->title_separator . $config->manager->title;
    } else {
        if($id !== false) {
            Shield::abort(); // File not found!
        }
        $article = Mecha::O(array(
            'id' => "",
            'path' => "",
            'state' => 'drafted',
            'date' => array('W3C' => ""),
            'title' => $config->defaults->article_title,
            'link' => "",
            'slug' => "",
            'content_raw' => $config->defaults->article_content,
            'content_type' => $config->html_parser,
            'description' => "",
            'kind' => array(),
            'author' => Guardian::get('author'),
            'css_raw' => $config->defaults->article_css,
            'js_raw' => $config->defaults->article_js,
            'fields' => array()
        ));
        $title = Config::speak('manager.title_new_', $speak->article) . $config->title_separator . $config->manager->title;
    }
    $G = array('data' => Mecha::A($article));
    Config::set(array(
        'page_title' => $title,
        'page' => $article,
        'html_parser' => $article->content_type,
        'cargo' => 'repair.post.php'
    ));
    if($request = Request::post()) {
        Guardian::checkToken($request['token']);
        $task_connect = $task_connect_page = $article;
        $task_connect_segment = 'article';
        $task_connect_page_css = $config->defaults->article_css;
        $task_connect_page_js = $config->defaults->article_js;
        include __DIR__ . DS . 'task.field.5.php';
        $extension = $request['action'] === 'publish' ? '.txt' : '.draft';
        $kind = isset($request['kind']) ? $request['kind'] : array(0);
        sort($kind);
        // Check for duplicate slug, except for the current old slug.
        // Allow user(s) to change their post slug, but make sure they
        // do not type the slug of another post.
        if(trim($slug) !== "" && $slug !== $article->slug && $files = Get::articles('DESC', "", 'txt,draft,archive')) {
            foreach($files as $file) {
                if(strpos(File::B($file), '_' . $slug . '.') !== false) {
                    Notify::error(Config::speak('notify_error_slug_exist', $slug));
                    Guardian::memorize($request);
                    break;
                }
            }
            unset($files);
        }
        $P = array('data' => $request);
        if( ! Notify::errors()) {
            include __DIR__ . DS . 'task.field.2.php';
            include __DIR__ . DS . 'task.field.1.php';
            include __DIR__ . DS . 'task.field.4.php';
            // Ignite
            if( ! $id) {
                Page::header($header)->content($content)->saveTo(ARTICLE . DS . Date::slug($date) . '_' . implode(',', $kind) . '_' . $slug . $extension);
                include __DIR__ . DS . 'task.custom.2.php';
                Notify::success(Config::speak('notify_success_created', $title) . ($extension === '.txt' ? ' <a class="pull-right" href="' . Filter::colon('article:url', $config->url . '/' . $config->index->slug . '/' . $slug) . '" target="_blank"><i class="fa fa-eye"></i> ' . $speak->view . '</a>' : ""));
                Weapon::fire(array('on_article_update', 'on_article_construct'), array($G, $P));
                Guardian::kick($config->manager->slug . '/article/repair/id:' . Date::format($date, 'U'));
            // Repair
            } else {
                Page::open($article->path)->header($header)->content($content)->save();
                File::open($article->path)->renameTo(Date::slug($date) . '_' . implode(',', $kind) . '_' . $slug . $extension);
                include __DIR__ . DS . 'task.custom.1.php';
                if($article->slug !== $slug && $php_file = File::exist(File::D($article->path) . DS . $article->slug . '.php')) {
                    File::open($php_file)->renameTo($slug . '.php');
                }
                Notify::success(Config::speak('notify_success_updated', $title) . ($extension === '.txt' ? ' <a class="pull-right" href="' . Filter::colon('article:url', $config->url . '/' . $config->index->slug . '/' . $slug) . '" target="_blank"><i class="fa fa-eye"></i> ' . $speak->view . '</a>' : ""));
                Weapon::fire(array('on_article_update', 'on_article_repair'), array($G, $P));
                // Rename all comment file(s) related to article if article date has been changed
                if(((string) $date !== (string) $article->date->W3C) && $comments = Get::comments('DESC', 'post:' . Date::slug($id), 'txt,hold')) {
                    foreach($comments as $comment) {
                        $parts = explode('_', File::B($comment));
                        $parts[0] = Date::slug($date);
                        File::open($comment)->renameTo(implode('_', $parts));
                    }
                }
                Guardian::kick($config->manager->slug . '/article/repair/id:' . Date::format($date, 'U'));
            }
        }
    }
    Weapon::add('unit_composer_1_before', function($page, $segment) use($config, $speak) {
        if(strpos($config->url_path, '/id:') !== false) {
            include __DIR__ . DS . 'unit.composer.1.1.php';
        }
    });
    Weapon::add('unit_composer_1_after', function($page, $segment) use($config, $speak) {
        include __DIR__ . DS . 'unit.composer.1.4.php';
        include __DIR__ . DS . 'unit.composer.1.3.php';
    });
    Weapon::add('SHIPMENT_REGION_BOTTOM', function() use($id) {
        echo ! $id ? '<script>
(function($) {
    $.slug($(\'input[name="title"]\'), $(\'input[name="slug"]\'), \'-\');
})(window.Zepto || window.jQuery);
</script>' : "";
    }, 11);
    Shield::lot(array('segment' => 'article'))->attach('manager');
});


/**
 * Article Killer
 * --------------
 */

Route::accept($config->manager->slug . '/article/kill/id:(:num)', function($id = "") use($config, $speak) {
    if( ! $article = Get::article($id, array('content', 'excerpt', 'tags'))) {
        Shield::abort();
    }
    if( ! Guardian::happy(1) && Guardian::get('author') !== $article->author) {
        Shield::abort();
    }
    Config::set(array(
        'page_title' => $speak->deleting . ': ' . $article->title . $config->title_separator . $config->manager->title,
        'page' => $article,
        'cargo' => 'kill.post.php'
    ));
    $G = array('data' => Mecha::A($article));
    if($request = Request::post()) {
        Guardian::checkToken($request['token']);
        File::open($article->path)->delete();
        // Deleting comment(s) ...
        if($comments = Get::comments('DESC', 'post:' . Date::slug($id), 'txt,hold')) {
            foreach($comments as $comment) {
                File::open($comment)->delete();
            }
        }
        $task_connect = $article;
        $P = array('data' => $request);
        include __DIR__ . DS . 'task.field.3.php';
        include __DIR__ . DS . 'task.custom.3.php';
        Notify::success(Config::speak('notify_success_deleted', $article->title));
        Weapon::fire(array('on_article_update', 'on_article_destruct'), array($G, $G));
        Guardian::kick($config->manager->slug . '/article');
    } else {
        Notify::warning(Config::speak('notify_confirm_delete_', '<strong>' . $article->title . '</strong>'));
        Notify::warning(Config::speak('notify_confirm_delete_page', strtolower($speak->article)));
    }
    Shield::lot(array('segment' => 'article'))->attach('manager');
});