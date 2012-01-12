<?php

$s = microtime(true);

$config = __DIR__ . '/../config.php';
if (file_exists($config)) {
    require $config;
}

define('VIEWDIR', __DIR__ . '/views');
define('UPLOADDIR', BASEDIR . '/uploads');

require __DIR__ . '/models/Item.php';
require __DIR__ . '/models/post.php';
require __DIR__ . '/models/link.php';
require __DIR__ . '/models/video.php';
require __DIR__ . '/models/audio.php';
require __DIR__ . '/models/image.php';
require __DIR__ . '/models/status.php';
require __DIR__ . '/libs/FeedItem.php';
require __DIR__ . '/libs/FeedWriter.php';
require __DIR__ . '/libs/predis_0.7.1.phar';
require __DIR__ . '/libs/redisclient.php';
require __DIR__ . '/libs/Form.php';
require __DIR__ . '/libs/paginate.php';
require __DIR__ . '/klein.php';
require __DIR__ . '/util.php';

// The router, will also dispactch. Should be the last thing require'd
require __DIR__ . '/routing.php';
