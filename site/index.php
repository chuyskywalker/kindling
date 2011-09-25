<?php

define('BASEDIR', __DIR__);
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
    	// editing an item or creating a new one
        $itemSlug= $request->param('id', false);
        $type    = $request->param('type');
        $url     = $request->param('url', false);
        $sel     = $request->param('sel', false);
        $doc     = $request->param('doc', false);
        $bm      = (bool) $request->param('bm', false);

        $class = 'Item_' . $type;
        if (!class_exists($class)) {
            die('Invalid type');
        }
        /** @var $itemClass Item */
        $itemClass = new $class();

        $itemId = $itemSlug && rc::get()->exists(rc::key(Item::REDIS_PREFIX, $itemSlug)) ? $itemSlug : false;

        $saved = $request->method('POST') ? $itemClass->save($itemId, $_POST) : null;

        if( $request->method('POST') && isset($_POST['delete']) && $_POST['delete'] == 'Delete'){
            $itemClass->delete($itemId);
            $response->flash('Post deleted');
            $response->redirect('/');
        }

        if ($saved === true) {
            $response->flash('Post saved');
            $response->redirect('/');
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
        $response->render('_footer.phtml', array());
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

    // todo: error out when tehre are no items (probably needs different treatement between type listing and homepage

    $response->render('list.phtml', compact('items', 'list', 'page', 'start', 'end'));

}

respond('/[a:type]/[i:page]', function (_Request $request, _Response $response, $app, $matched) {
	    // list of items by given type
        $type = $request->param('type', false);
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
