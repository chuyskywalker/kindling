<?php

require __DIR__ . '/../config.php';

define('VIEWDIR', __DIR__ . '/views/');

require __DIR__ . '/models/Item.php';
require __DIR__ . '/models/post.php';
require __DIR__ . '/models/link.php';
require __DIR__ . '/models/video.php';
require __DIR__ . '/models/audio.php';
require __DIR__ . '/models/image.php';
require __DIR__ . '/libs/redisclient.php';
require __DIR__ . '/libs/Form.php';
require __DIR__ . '/klein.php';
require __DIR__ . '/util.php';
