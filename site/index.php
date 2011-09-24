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

respond('/[a:item]', function (_Request $request, _Response $response, $app) {
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

respond('/[a:type]/[i:page]', function (_Request $request, _Response $response, $app) {
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
