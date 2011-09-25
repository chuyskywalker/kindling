<?php

require __DIR__.'/../code/all.php';

startSession();

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

respond('/edit/[a:type]/[:id]?', function (_Request $request, _Response $response, $app, $matched) {
    	// editing an item or creating a new one
        $itemSlug= $request->param('id', false);
        $type    = $request->param('type');

        $url     = $request->param('url', false);
        $content = $request->param('content', false);

        $class = 'Item_' . $type;
        if (!class_exists($class)) {
            die('Invalid type');
        }
        /** @var $itemClass Item */
        $itemClass = new $class();

        $itemId = $itemSlug && rc::get()->exists(rc::key(Item::REDIS_PREFIX, $itemSlug)) ? $itemSlug : false;

        $saved = $request->method('POST') ? $itemClass->save($itemId, $_POST) : null;

        if ($saved === true) {
            $response->flash('Post saved');
            $response->redirect('/');
        }

        $editing = $itemId !== false;

        $itemDetails = $editing ? rc::get()->hGetAll(rc::key(Item::REDIS_PREFIX, $itemId)) : false;

        $response->render(VIEWDIR.'edit.phtml', array(
              'itemId' => $itemId
            , 'type' => $type
            , 'saved' => $saved
            , 'errors' => $itemClass->getErrors()
            , 'fields' => $itemClass->getFields()
            , 'itemDetails' => $itemDetails
            , 'editing' => $editing
        ));
        
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
        var_dump($itemDetails);
});

function renderList(_Response $response, $list, $page) {

    if ($list != Item::REDIS_ALLPOSTS && !class_exists('Item_' . $list)) {
        die('Invalid type');
    }

    $zset = rc::key(Item::REDIS_POST_INDEX, $list);
    if (!rc::get()->exists($zset)) {
        die('No content here');
        // todo: handle better :)
    }

    $start = ($page - 1) * PERPAGE;
    $end   = $start + PERPAGE - 1;

    $itemList = rc::get()->zRevRange($zset, (int)$start, (int)$end);

    $items = array();

    if (count($itemList)) {
        foreach ($itemList as $itemId) {
            $items[] = rc::get()->hGetAll(rc::key(Item::REDIS_PREFIX, $itemId));
        }
    }

    $response->render(VIEWDIR.'list.phtml', compact('items', 'list', 'page', 'start', 'end'));

    // todo: error out when tehre are no items
    // todo: display items in list
}

respond('/[a:type]/[i:page]', function (_Request $request, _Response $response, $app, $matched) {
	    // list of items by given type
        $type = $request->param('type', false);
        $page = $request->param('page', 1);
        renderList($response, $type, $page);
});

$homepage = function (_Request $request, _Response $response, $app, $matched) {
    // homepage list
    if ($matched > 0) {
        return false;
    }
    $page = $request->param('page', 1);
    renderList($response, Item::REDIS_ALLPOSTS, $page);
    
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
