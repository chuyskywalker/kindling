<?php

require __DIR__.'/../code/all.php';

// fix lighttp query string
if (strstr($_SERVER['REQUEST_URI'],'?') !== false && empty($_SERVER['QUERY_STRING'])) {
	$_SERVER['QUERY_STRING'] = preg_replace('#^.*?\?#','',$_SERVER['REQUEST_URI']);
	parse_str($_SERVER['QUERY_STRING'], $_GET);
}

respond('*', function (_Request $request, _Response $response, $app) {
//	$cc = isset($_COOKIE['cc']) && in_array($_COOKIE['cc'], History::$currencies) ? $_COOKIE['cc'] : History::CURRENCY_US;
//	$response->set('cc', $cc);
//	$response->set('metaDesc', 'Meta');
	return false;
});

respond('/edit/[i:id]?', function (_Request $request, _Response $response, $app, $matched) {
    	// editing an item or creating a new one
        $itemId  = $request->param('id', false);
        $url     = $request->param('url', false);
        $content = $request->param('content', false);
        $type    = $request->param('type', 'Post');

        $class = 'Item_' . $type;
        if (!class_exists($class)) {
            die('Invalid type');
        }
        /** @var $itemClass Item */
        $itemClass = new $class();

        $itemDetails = false;

        if ($itemId === false || empty($itemId)) {
            $editing = false;
            // create
            if ($request->method('POST')) {
                // check and save item
                try {
                    $itemClass->save($itemId, $_POST);
                    // todo: display success
                }
                catch (Exception $e) {
                    // TODO: Respond with can't save
                }
            }
        }
        else {
            $editing = true;
            // edit
            if ($request->method('POST')) {
                // check edit and save item
                try {
                    $itemClass->save($itemId, $_POST);
                    // todo: display success
                }
                catch (Exception $e) {
                    // TODO: Respond with can't save
                }
            }
            else {
                // present the edit form for given item id
                $itemDetails = rc::get()->hGetAll(rc::key(Item::REDIS_PREFIX, $itemId));
            }
        }

        $response->render(VIEWDIR.'edit.phtml', array(
              'itemId' => $itemId
            , 'type' => $type
            , 'itemDetails' => $itemDetails
        ));

//        rc::get()->hSet(rc::key(Item::REDIS_PREFIX, $itemId), 'title', 'test title');
//        rc::get()->hSet(rc::key(Item::REDIS_PREFIX, $itemId), 'type', 'type');
//
//        rc::get()->hSet(rc::key(Item::REDIS_PREFIX, $itemId), 'content', 'content goes here');
//        rc::get()->hSet(rc::key(Item::REDIS_PREFIX, $itemId), 'url', 'content goes here');
//        rc::get()->hSet(rc::key(Item::REDIS_PREFIX, $itemId), 'mp3', 'content goes here');
//        rc::get()->hSet(rc::key(Item::REDIS_PREFIX, $itemId), 'video', 'content goes here');

        $itemDetails = rc::get()->hGetAll(rc::key(Item::REDIS_PREFIX, $itemId));
        if (count($itemDetails) == 0) {
            return false;
        }
        var_dump($itemDetails);
});

respond('/[a:item]', function (_Request $request, _Response $response, $app, $matched) {
    	// single item
        $itemId = $request->param('item', false);
        if ($itemId === false || empty($itemId)) {
            return false;
        }
//        rc::get()->hSet(rc::key(Item::REDIS_PREFIX, $itemId), 'title', 'test title');
//        rc::get()->hSet(rc::key(Item::REDIS_PREFIX, $itemId), 'type', 'type');
//
//        rc::get()->hSet(rc::key(Item::REDIS_PREFIX, $itemId), 'content', 'content goes here');
//        rc::get()->hSet(rc::key(Item::REDIS_PREFIX, $itemId), 'url', 'content goes here');
//        rc::get()->hSet(rc::key(Item::REDIS_PREFIX, $itemId), 'mp3', 'content goes here');
//        rc::get()->hSet(rc::key(Item::REDIS_PREFIX, $itemId), 'video', 'content goes here');

        $itemDetails = rc::get()->hGetAll(rc::key(Item::REDIS_PREFIX, $itemId));
        if (count($itemDetails) == 0) {
            return false;
        }
        var_dump($itemDetails);
});

respond('/[a:type]/[i:page]', function (_Request $request, _Response $response, $app, $matched) {
	    // list of items by given type
        $type = $request->param('type', false);
        $page = $request->param('page', 1);
        var_dump($type);
        var_dump($page);
});

$homepage = function (_Request $request, _Response $response, $app, $matched) {
    // homepage list
    if ($matched > 0) {
        return false;
    }
    $page = $request->param('page', 1);
    if ($page > 2) {
        return false;
    }
    var_dump($page);
    return true;
};
respond('/[i:page]?', $homepage);
respond('/', $homepage);

respond('*', function (_Request $request, _Response $response, $app, $matched) {
        if ($matched === 0) {
            $response->code(404);
            $response->render(VIEWDIR.'404.phtml');
        }
});

dispatch();
