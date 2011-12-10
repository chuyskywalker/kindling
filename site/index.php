<?php
$s = microtime(true);
define('BASEDIR', __DIR__);
require __DIR__.'/../code/all.php';

// fix lighttp query string
if (strstr($_SERVER['REQUEST_URI'],'?') !== false && empty($_SERVER['QUERY_STRING'])) {
    $_SERVER['QUERY_STRING'] = preg_replace('#^.*?\?#','',$_SERVER['REQUEST_URI']);
    parse_str($_SERVER['QUERY_STRING'], $_GET);
}

try {

respond('*', function (_Request $request, _Response $response, $app) {

        // figure out the site URL
        $siteurl = rtrim(defined('URL') ? URL : 'http://'.$_SERVER['HTTP_HOST'], '/');
        $response->set('siteurl', $siteurl);
        $app->siteurl = $siteurl;

        // always run hasAuth eveyr request -- helps keep the "session" alive as it will reset the auth cookie for logged in users
        $app->hasAuth = hasAuth();
        $response->set('hasAuth', $app->hasAuth);

        // get the start/end of this blogs post history
        $allItems = rc::key(Item::REDIS_POST_INDEX, Item::REDIS_ALLPOSTS);
        $firstItem = rc::get()->zRange($allItems, 0, 0, true);
        $lastItem  = rc::get()->zRevRange($allItems, 0, 0, true);
        if ($firstItem && $lastItem) {
            $response->set('startdate', array_pop($firstItem));
            $response->set('enddate', array_pop($lastItem));
        }
        else {
            $response->set('startdate', time());
            $response->set('enddate', time());
        }
        return false;
});

respond('/auth/login', function (_Request $request, _Response $response, $app, $matched) {

        if (!defined('PASSWORD')) {
            die('You must setup config.php to have a <br/><br/><code>define(\'PASSWORD\', \'valuehere\');</code><br/><br/> line. The value must be a SHA1 hash of your password.');
        }
        else {
            if ($request->method('POST') && isset($_POST['password']) && sha1($_POST['password']) == PASSWORD){
                giveAuth();
                // todo: redirect back to where the user was trying to get to
                $response->redirect('/');
            }
            $response->render('login.phtml');
        }

});

respond('/bm/close', function (_Request $request, _Response $response, $app, $matched) {

        echo 'Posted. Closing in <span id="close">3</span>. <a href="javascript:self.close()">Close now</a>.
        <script>
            var d = document.getElementById("close");
            var ms = new Date().getTime();
            setInterval(function() {
                var ns = new Date().getTime();
                var cin = ns - ms;
                d.innerHTML = 3 - Math.floor(cin/1000);
                if (cin > 3000) {
                    self.close();
                }
            }, 150);
        </script>
        ';

});

respond('/bm/go', function (_Request $request, _Response $response, $app, $matched) {

        $url = $request->param('url', false);
        $sel = $request->param('sel', false);
        $doc = $request->param('doc', false);

        $type = Item_post::TYPE;

        if ($url) {
            if (preg_match(Form::REGEX_URL_AUDIO, $url)) {
                $type = Item_audio::TYPE;
            }
            elseif (preg_match(Form::REGEX_URL_IMAGE, $url)) {
                $type = Item_image::TYPE;
            }
            elseif (preg_match(Form::REGEX_URL_VIDEO, $url)) {
                $type = Item_video::TYPE;
            }
            elseif (preg_match(Form::REGEX_URL, $url)) {
                $type = Item_link::TYPE;
            }
        }

        $response->redirect('/edit/' . $type . '?' . http_build_query(array(
              'url' => $url
            , 'doc' => $doc
            , 'sel' => $sel
            , 'bm'  => 1
        )));

});

respond('/edit/[a:type]/[:id]?', function (_Request $request, _Response $response, $app, $matched) {

        if (!$app->hasAuth) {
            $response->redirect('/auth/login');
        }
        
        // editing an item or creating a new one
        $itemSlug= $request->param('id', false);
        $type    = $request->param('type');
        $url     = $request->param('url', false);
        $sel     = $request->param('sel', false);
        $doc     = $request->param('doc', false);
        $bm      = (bool) $request->param('bm', false);

        $class = 'Item_' . $type;
        if (class_exists($class) !== true) {
            return false;
        }
        /** @var $itemClass Item */
        $itemClass = new $class();

        $itemId = $itemSlug && rc::get()->exists(rc::key(Item::REDIS_PREFIX, $itemSlug)) ? $itemSlug : false;

        $saved = $request->method('POST') ? $itemClass->save($itemId, $_POST) : null;

        if ($request->method('POST') && isset($_POST['delete']) && $_POST['delete'] == 'Delete'){
            $itemClass->delete($itemId);
            $response->flash('Post deleted');
            $response->redirect('/');
        }

        if ($saved === true) {
            if ($bm) {
                $response->redirect('/bm/close');
            }
            else {
                $response->flash('Post saved');
                $response->redirect('/'.$itemId);
            }
        }

        $editing = $itemId !== false;

        $itemDetails = $editing ? rc::get()->hGetAll(rc::key(Item::REDIS_PREFIX, $itemId)) : false;

        if (!$editing && !$request->method('POST')) {
            // push in some fake item details
            $itemDetails['title']        = !empty($doc) ? $doc : '';
            $itemDetails['url']          = !empty($url) ? $url : '';
            $itemDetails['url_filename'] = !empty($url) ? $url : '';
            $itemDetails['content']      = !empty($sel) ? $sel : '';
            $itemDetails['comment']      = !empty($sel) ? $sel : '';
        }

        $response->render('edit.phtml', array(
              'itemId' => $itemId
            , 'bm' => $bm
            , 'type' => $type
            , 'saved' => $saved
            , 'errors' => $itemClass->getErrors()
            , 'fields' => $itemClass->getFields()
            , 'itemDetails' => $itemDetails
            , 'editing' => $editing
        ));
        
});

respond('/edit/[:page]?', function (_Request $request, _Response $response, $app, $matched) {

    if ($matched > 0) {
        return false;
    }

	if (!$app->hasAuth) {
		$response->redirect('/auth/login');
	}

    $page = (int)$request->param('page', 1);

    $zset = rc::key(Item::REDIS_POST_INDEX, Item::REDIS_ALLPOSTS);
    if (!rc::get()->exists($zset)) {
        $response->render('list.phtml', compact('list'));
        return;
    }

    $perpage = (defined('ADMIN_PERPAGE') ? ADMIN_PERPAGE : 10);
    $start = ($page - 1) * $perpage;
    $end   = $start + $perpage - 1;

    $itemList = rc::get()->zRevRange($zset, (int)$start, (int)$end);

    $items = array();

    if (count($itemList)) {
        foreach ($itemList as $itemId) {
            $items[] = rc::get()->hGetAll(rc::key(Item::REDIS_PREFIX, $itemId));
        }
    }

    $total = rc::get()->zCard($zset);

    $response->render('edit_list.phtml', compact('items', 'list', 'page', 'start', 'end', 'total', 'perpage'));

});

respond('/feed/[(rss|rss2|atom):type]', function (_Request $request, _Response $response, $app, $matched) {

        // if no items, 404 it
        $zset = rc::key(Item::REDIS_POST_INDEX, Item::REDIS_ALLPOSTS);
        if (!rc::get()->exists($zset)) {
            $response->code(404);
            return;
        }
        
        // determine format
        switch ($request->param('type')) {
            case 'atom': $type = ATOM; break;
            case 'rss': $type = RSS1; break;
            case 'rss2': default: $type = RSS2; break;
        }
        
        // setup the basics
        $TestFeed = new FeedWriter($type);
        $TestFeed->setTitle(defined('TITLE') ? TITLE : 'Tumblite');
        $TestFeed->setLink($app->siteurl);
        $TestFeed->setDescription(defined('DESCRIPTION') ? DESCRIPTION : 'A blog by ' . (defined('AUTHOR') ? AUTHOR : 'Anonymous'));

        // get the list of recent items
        $page = 1;
        $perpage = (defined('PERPAGE') ? PERPAGE : 10);
        $start = ($page - 1) * $perpage;
        $end   = $start + $perpage - 1;

        $itemList = rc::get()->zRevRange($zset, (int)$start, (int)$end);

        // loop items, have party
        if (count($itemList)) {
            foreach ($itemList as $itemId) {
                $itemDetails = rc::get()->hGetAll(rc::key(Item::REDIS_PREFIX, $itemId));
                
                $newItem = $TestFeed->createNewItem();

                // make with the pretty feed item elements
                $newItem->setTitle($itemDetails['title']);
                $newItem->setLink($app->siteurl .'/'. $itemDetails['slug']);
                $newItem->setDate($itemDetails['createdAt']);

                // faaaancy
                switch ($itemDetails['type']) {
                    case Item_image::TYPE:
                        $newItem->setDescription('<p><img src="' . $app->siteurl . image($itemDetails['filename'], array('w'=>640)) .'" alt="" /></p>' . $itemDetails['commentRendered']);
                        break;
                    default:
                        if (isset($itemDetails['commentRendered'])) {
                            $newItem->setDescription($itemDetails['commentRendered']);
                        }
                        break;
                }

                // Add the feed item
                $TestFeed->addItem($newItem);
            }
        }

        // Spit it out
        $TestFeed->genarateFeed();
});

respond('/[:item]', function (_Request $request, _Response $response, $app, $matched) {
        if ($matched > 0) { 
            return false;
        }
        // single item
        $itemId = $request->param('item', false);
        if ($itemId === false || empty($itemId)) {
            return false;
        }

        $itemDetails = rc::get()->hGetAll(rc::key(Item::REDIS_PREFIX, $itemId));
        if (count($itemDetails) == 0) {
            return false;
        }

        // TODO: determine previous & next item

        $response->render('_head.phtml', array('title'=>$itemDetails['title']));
        $response->render('item.phtml', array('item'=>$itemDetails));
        if ($itemDetails['_is_static'] == 0) {
            $response->render('disqus.phtml', array('item'=>$itemDetails));
        }
        $response->render('_footer.phtml', array());

        return true;
});

function renderList(_Response $response, $list, $page) {

    if ($list != Item::REDIS_ALLPOSTS && !class_exists('Item_' . $list)) {
        $response->redirect('/');
    }

    $zset = rc::key(Item::REDIS_POST_INDEX, $list);
    if (!rc::get()->exists($zset)) {
        $response->render('list.phtml', compact('list'));
        return;
    }

    $perpage = (defined('PERPAGE') ? PERPAGE : 10);
    $start = ($page - 1) * $perpage;
    $end   = $start + $perpage - 1;

    $itemList = rc::get()->zRevRange($zset, (int)$start, (int)$end);

    $items = array();

    if (count($itemList)) {
        foreach ($itemList as $itemId) {
            $items[] = rc::get()->hGetAll(rc::key(Item::REDIS_PREFIX, $itemId));
        }
    }

    $total = rc::get()->zCard($zset);

    if (count($items) == 0) {
        if ($list == Item::REDIS_ALLPOSTS) {
            $response->redirect('/');
        }
        else {
            $response->redirect("/$list/1");
        }
    }

    if ($list != Item::REDIS_ALLPOSTS || $page != 1) {
        $response->set('title', ucfirst($list) . ' Items, Page ' . $page);
    }
    
    $response->render('list.phtml', compact('items', 'list', 'page', 'start', 'end', 'total', 'perpage'));

}

respond('/[a:type]/[i:page]', function (_Request $request, _Response $response, $app, $matched) {
        // list of items by given type
        $type = $request->param('type', false);
        if ($type == 'edit') {
            // nope, this is an edit page, ignore this.
            return false;
        }
        $page = (int)$request->param('page', 1);
        // don't allow people to hit the /all/ category listings -- it's redundant, use /:page instead
        if ($type == Item::REDIS_ALLPOSTS) {
            $response->redirect('/' . $page);
        }
        renderList($response, $type, $page);
});

$homepage = function (_Request $request, _Response $response, $app, $matched) {
    // homepage list
    if ($matched > 0) {
        return false;
    }
    $page = (int)$request->param('page', 1);
    renderList($response, Item::REDIS_ALLPOSTS, $page);
    
    return true;
};
respond('/[i:page]?', $homepage);
respond('/', $homepage);

respond('*', function (_Request $request, _Response $response, $app, $matched) {
        if ($matched === 0) {
            $response->code(404);
            $response->render('404.phtml');
        }
});

dispatch();

$m = number_format(microtime(true) - $s, 6);
echo "\n<!-- rendered in: $m seconds -->";

}
catch (Exception $e) {
    // Usually means Redis is offline
    echo 'Sorry, this blog is offline currently.';
}