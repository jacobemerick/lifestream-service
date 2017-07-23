<?php

date_default_timezone_set('America/Phoenix');

$startTime = microtime(true);
$startMemory = memory_get_usage();

require_once __DIR__ . '/../vendor/autoload.php';

use Aura\Di\ContainerBuilder;
use Aura\Sql\ExtendedPdo;

// load the config for the application
$config_path = __DIR__ . '/../config.json';

$handle = @fopen($config_path, 'r');
if ($handle === false) {
    throw new RuntimeException('Could not load config');
}
$config = fread($handle, filesize($config_path));
fclose($handle);

$config = json_decode($config);
$last_json_error = json_last_error();
if ($last_json_error !== JSON_ERROR_NONE) {
    throw new RuntimeException('Could not parse config - JSON error detected');
}

$builder = new ContainerBuilder();
$di = $builder->newInstance($builder::AUTO_RESOLVE);

// pass config into container
$di->set('config', $config);

// global time object
$di->set('timezone', new DateTimeZone('America/Phoenix'));
$di->set('datetime', new DateTime('now', new DateTimeZone('America/Phoenix')));

// set up db and models
$di->set('dbal', $di->lazyNew(
    'Aura\Sql\ExtendedPdo',
    (array) $config->database
));
$di->types['Aura\Sql\ExtendedPdo'] = $di->lazyGet('dbal');

$di->set('blogCommentModel', $di->lazyNew('Jacobemerick\LifestreamService\Model\BlogComment'));
$di->set('blogModel', $di->lazyNew('Jacobemerick\LifestreamService\Model\Blog'));
$di->set('distanceModel', $di->lazyNew('Jacobemerick\LifestreamService\Model\Distance'));
$di->set('typeModel', $di->lazyNew('Jacobemerick\LifestreamService\Model\Type'));

// set up clients
$di->set('blogClient', $di->lazyNew(
    'GuzzleHttp\Client',
    [[
        'base_uri' => $config->blog->baseUri,
        'headers' => [
            'User-Agent' => 'lifestream-service/1.0',
            'Accept' => 'application/xml',
        ],
    ]]
));

$di->set('distanceClient', $di->lazyNew(
    'GuzzleHttp\Client',
    [[
        'base_uri' => $config->distance->baseUri,
        'headers' => [
            'User-Agent' => 'lifestream-service/1.0',
            'Accept' => 'application/json',
        ],
    ]]
));

// set up clients
$di->set('blogClient', $di->lazyNew(
    'GuzzleHttp\Client',
    [[
        'base_uri' => $config->blog->baseUri,
        'headers' => [
            'User-Agent' => 'lifestream-service/1.0',
            'Accept' => 'application/xml',
        ],
    ]]
));


// switch to determine which cron to run
$opts = getopt('s:');
if (!$opts['s']) {
    throw new Exception('Must specify a -s flag to determine which cron to run');
}

// set up logger
$di->set('logger', $di->lazyNew(
    'Monolog\Logger',
    [
        'name' => 'default',
    ],
    [
        'pushHandler' => $di->lazyNew(
            'Monolog\Handler\StreamHandler',
            [
                'stream' => __DIR__ . "/../logs/{$opts['s']}.log",
                'level' => Monolog\Logger::DEBUG,
            ]
        ),
    ]
));

use Jacobemerick\LifestreamService\Cron;

switch ($opts['s']) {
    case 'blog':
        $cron = new Cron\Blog($di);
        break;
    case 'blogComment':
        $cron = new Cron\BlogComment($di);
        break;
    case 'distance':
        $cron = new Cron\Distance($di);
        break;
    default:
        throw new Exception('Unrecognized cron passed in');
        break;
}

$cron->setLogger($di->get('logger'));
$cron->run();

$di->get('logger')->addInfo('Runtime stats', [
    'time' => (microtime(true) - $startTime),
    'memory' => (memory_get_usage() - $startMemory),
]);
