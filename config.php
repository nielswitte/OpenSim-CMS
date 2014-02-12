<?php
    define('EXEC', 					'1');
    // Server settings
    define('SERVER_PROTOCOL',       'http');
    define('SERVER_ADDRESS',        'localhost');
    define('SERVER_PORT',           62535);
    define('SERVER_ROOT',           '/CMS');

    // OpenSim settings
    define('OS_ASSET_CACHE_EXPIRES','48 hours');

    // Files settings
	define('FILES_LOCATION', 		'C:\\SoaS\\SoaS076\\www\\storage');
	define('DS', 					'\\');
	define('PRESENTATIONS', 		'presentations');

    // Image settings
	define('IMAGE_WIDTH',			1024);
	define('IMAGE_HEIGHT',			1024);

    require_once dirname(__FILE__) .'/includes/class.MysqliDB.php';
    require_once dirname(__FILE__) .'/includes/class.Helper.php';

    $db = new Mysqlidb('localhost', 'root', '', 'cms', 3307);

    Helper::setDB($db);

