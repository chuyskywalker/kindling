<?php

require __DIR__ . '/../config.php';

define('VIEWDIR', __DIR__ . '/views/');

require __DIR__ . '/models/Item.php';
require __DIR__ . '/libs/redisclient.php';
require __DIR__ . '/klein.php';
require __DIR__ . '/util.php';
