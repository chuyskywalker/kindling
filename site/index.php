<?php

require __DIR__.'/../code/all.php';

// fix lighttp query string
if (strstr($_SERVER['REQUEST_URI'],'?') !== false && empty($_SERVER['QUERY_STRING'])) {
	$_SERVER['QUERY_STRING'] = preg_replace('#^.*?\?#','',$_SERVER['REQUEST_URI']);
	parse_str($_SERVER['QUERY_STRING'], $_GET);
}

respond('*', function (_Request $request, _Response $response) {
//	$cc = isset($_COOKIE['cc']) && in_array($_COOKIE['cc'], History::$currencies) ? $_COOKIE['cc'] : History::CURRENCY_US;
//	$response->set('cc', $cc);
	$response->set('metaDesc', 'Meta');
	return false;
});

respond('/[a:item]', function (_Request $request, _Response $response) {
	// single item
});

respond('/[a:type]/[i:page]', function (_Request $request, _Response $response) {
	// list of items by given type
});

respond('/[i:page]?', function (_Request $request, _Response $response) {
	// homepage list
});

respond('*', function (_Request $request, _Response $response, $app, $matched) {
	if ($matched === false) {
		$response->code(404);
		$response->render(VIEWDIR.'404.phtml');
	}
});

dispatch();
