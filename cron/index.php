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
$di->set('bookModel', $di->lazyNew('Jacobemerick\LifestreamService\Model\Book'));
$di->set('codeModel', $di->lazyNew('Jacobemerick\LifestreamService\Model\Code'));
$di->set('distanceModel', $di->lazyNew('Jacobemerick\LifestreamService\Model\Distance'));
$di->set('eventModel', $di->lazyNew('Jacobemerick\LifestreamService\Model\Event'));
$di->set('photoModel', $di->lazyNew('Jacobemerick\LifestreamService\Model\Photo'));
$di->set('twitterModel', $di->lazyNew('Jacobemerick\LifestreamService\Model\Twitter'));
$di->set('typeModel', $di->lazyNew('Jacobemerick\LifestreamService\Model\Type'));
$di->set('userModel', $di->lazyNew('Jacobemerick\LifestreamService\Model\User'));
$di->set('videoModel', $di->lazyNew('Jacobemerick\LifestreamService\Model\Video'));

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

$di->set('bookClient', $di->lazyNew(
    'GuzzleHttp\Client',
    [[
        'base_uri' => $config->book->baseUri,
        'headers' => [
            'User-Agent' => 'lifestream-service/1.0',
            'Accept' => 'application/xml',
        ],
    ]]
));

$di->set('codeClient', $di->lazyNew('Github\Client'));
$di->set('codeClientPager', $di->lazyNew(
    'Github\ResultPager',
    [
        'client' => $di->lazyGet('codeClient'),
    ]
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

$di->set('photoClient', $di->lazyNew(
    'GuzzleHttp\Client',
    [[
        'base_uri' => $config->photo->baseUri,
        'headers' => [
            'User-Agent' => 'lifestream-service/1.0',
            'Accept' => 'application/json',
        ],
    ]]
));

$di->set('twitterClient', $di->lazyNew(
    'Abraham\TwitterOAuth\TwitterOAuth',
    [
        'consumerKey' => $config->twitter->consumerKey,
        'consumerSecret' => $config->twitter->consumerSecret,
        'oauthToken' => $config->twitter->oauthToken,
        'oauthSecret' => $config->twitter->oauthSecret,
    ]
));

$di->set('videoClient', $di->lazyNew(
    'Madcoda\Youtube\Youtube',
    [[
        'key' => $config->video->key,
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

use Jacobemerick\LifestreamService\Cron\Fetch;
use Jacobemerick\LifestreamService\Cron\Process;

switch ($opts['s']) {
    case 'blog':
        $cron = new Process\Blog($di);
        break;
    case 'blogComment':
        $cron = new Fetch\BlogComment($di);
        break;
    case 'book':
        $cron = new Fetch\Book($di);
        break;
    case 'code':
        $cron = new Fetch\Code($di);
        break;
    case 'distance':
        $cron = new Fetch\Distance($di);
        break;
    case 'photo':
        $cron = new Fetch\Photo($di);
        break;
    case 'twitter':
        $cron = new Fetch\Twitter($di);
        break;
    case 'video':
        $cron = new Fetch\Video($di);
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
